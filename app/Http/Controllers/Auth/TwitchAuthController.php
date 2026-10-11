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
        if (
            !config('services.twitch.client_id') ||
            !config('services.twitch.client_secret') ||
            !config('services.twitch.redirect')
        ) {
            return redirect()
                ->route('home')
                ->with('error', 'A integração com a Twitch ainda não foi configurada.');
        }

        return Socialite::driver('twitch')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $twitchUser = Socialite::driver('twitch')->user();

            $twitchId = $twitchUser->getId();

            if (!$twitchId) {
                return redirect()
                    ->route('home')
                    ->with('error', 'Não foi possível identificar sua conta Twitch.');
            }

            $user = User::where('twitch_id', $twitchId)->first();

            if (!$user) {
                $user = new User();

                $user->twitch_id = $twitchId;
                $user->password = Hash::make(Str::random(64));
                $user->email = null;
            }

            $user->name = $twitchUser->getName()
                ?: $twitchUser->getNickname()
                ?: 'Jogador Twitch';

            $user->twitch_username = $twitchUser->getNickname()
                ?: $user->twitch_username;

            $user->twitch_avatar = $twitchUser->getAvatar();

            $user->save();

            Auth::login($user);
            request()->session()->regenerate();

            return redirect()
                ->route('home')
                ->with('success', 'Login realizado com sucesso!');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('home')
                ->with('error', 'Não foi possível entrar com a Twitch. Tente novamente.');
        }
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
