<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class TwitchAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (!$this->isConfigured()) {
            return $this->configurationError();
        }

        return Socialite::driver('twitch')->redirect();
    }

    public function link(): RedirectResponse
    {
        if (!$this->isConfigured()) {
            return $this->configurationError();
        }

        $user = Auth::user();
        if ($user->twitch_id) {
            return redirect()->route('home')->with('error', 'Esta conta já possui uma Twitch vinculada.');
        }

        request()->session()->put('twitch_link_user_id', $user->id);

        return Socialite::driver('twitch')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $twitchUser = Socialite::driver('twitch')->user();

            $twitchId = (string) $twitchUser->getId();

            if (!$twitchId) {
                return redirect()
                    ->route('home')
                    ->with('error', 'Não foi possível identificar sua conta Twitch.');
            }

            $linkUserId = request()->session()->pull('twitch_link_user_id');
            if (request()->session()->pull('twitch_broadcaster_auth')) {
                return $this->storeBroadcasterToken($twitchUser, $twitchId);
            }
            if ($linkUserId !== null) {
                return $this->linkAccount($twitchUser, $twitchId, (int) $linkUserId);
            }

            if (Auth::check()) {
                return redirect()->route('home')->with('error', 'Inicie o vínculo da Twitch pela sua conta.');
            }

            $user = User::where('twitch_id', $twitchId)->first();

            if (!$user) {
                $user = new User();
                $user->password = Hash::make(Str::random(64));
                $user->email = null;
            }

            $this->syncTwitchProfile($user, $twitchUser, $twitchId);
            $user->save();

            Auth::login($user);
            request()->session()->regenerate();

            return redirect()
                ->route('home')
                ->with('success', 'Login realizado com sucesso!');
        } catch (Throwable $exception) {
            request()->session()->forget('twitch_link_user_id');
            report($exception);

            return redirect()
                ->route('home')
                ->with('error', 'Não foi possível entrar com a Twitch. Tente novamente.');
        }
    }

    private function storeBroadcasterToken(object $twitchUser, string $twitchId): RedirectResponse
    {
        $channel = strtolower((string) config('services.twitch.channel_login'));

        if (Auth::user()?->role !== 'admin' || strtolower((string) $twitchUser->getNickname()) !== $channel) {
            return redirect()->route('admin.rewards.index')
                ->with('error', "Autorize com a conta do canal {$channel}.");
        }

        \App\Models\TwitchBroadcasterToken::query()->delete();
        \App\Models\TwitchBroadcasterToken::create([
            'twitch_id' => $twitchId,
            'login' => $channel,
            'access_token' => $twitchUser->token,
            'refresh_token' => $twitchUser->refreshToken,
            'expires_at' => now()->addSeconds((int) ($twitchUser->expiresIn ?: 3600)),
        ]);

        return redirect()->route('admin.rewards.index')->with('success', 'Canal autorizado: os pontos já podem ser contados.');
    }

    private function linkAccount(object $twitchUser, string $twitchId, int $userId): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || $user->id !== $userId) {
            return redirect()->route('home')->with('error', 'Sua sessão expirou. Entre novamente para vincular a Twitch.');
        }

        if ($user->twitch_id && $user->twitch_id !== $twitchId) {
            return redirect()->route('home')->with('error', 'Esta conta já está vinculada a outra Twitch.');
        }

        $alreadyLinked = User::query()
            ->where('twitch_id', $twitchId)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($alreadyLinked) {
            return redirect()->route('home')->with('error', 'Esta conta Twitch já está vinculada a outro usuário.');
        }

        $this->syncTwitchProfile($user, $twitchUser, $twitchId);
        $user->save();

        return redirect()->route('home')->with('success', 'Conta Twitch vinculada com sucesso.');
    }

    private function syncTwitchProfile(User $user, object $twitchUser, string $twitchId): void
    {
        $user->twitch_id = $twitchId;
        $user->name = $twitchUser->getName()
            ?: $twitchUser->getNickname()
            ?: 'Jogador Twitch';
        $user->twitch_username = $twitchUser->getNickname()
            ?: $user->twitch_username;
        $user->twitch_avatar = $twitchUser->getAvatar();

        if (in_array($twitchId, config('services.twitch.admin_ids', []), true)) {
            $user->role = 'admin';
        }
    }

    private function isConfigured(): bool
    {
        return (bool) config('services.twitch.client_id')
            && (bool) config('services.twitch.client_secret')
            && (bool) config('services.twitch.redirect');
    }

    private function configurationError(): RedirectResponse
    {
        return redirect()
            ->route('home')
            ->with('error', 'A integração com a Twitch ainda não foi configurada.');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'Você saiu da sua conta.');
    }
}
