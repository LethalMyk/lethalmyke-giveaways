<?php

namespace App\Services;

use App\Models\TwitchBroadcasterToken;
use App\Models\User;
use App\Models\ViewerPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ViewerPointsAwarder
{
    public function __construct(private TwitchLiveStatus $live)
    {
    }

    /** @return array{status: string, awarded: int} */
    public function run(): array
    {
        $clientId = config('services.twitch.client_id');
        $secret = config('services.twitch.client_secret');
        $token = TwitchBroadcasterToken::query()->first();

        if (! $clientId || ! $secret || ! $token) {
            return ['status' => 'not_authorized', 'awarded' => 0];
        }

        if (! ($this->live->status(true)['live'] ?? false)) {
            return ['status' => 'offline', 'awarded' => 0];
        }

        if (! $this->refreshIfNeeded($token, $clientId, $secret)) {
            return ['status' => 'token_error', 'awarded' => 0];
        }

        $twitchIds = $this->chatters($token, $clientId);
        if ($twitchIds === null) {
            return ['status' => 'api_error', 'awarded' => 0];
        }

        return ['status' => 'ok', 'awarded' => $this->award($twitchIds)];
    }

    private function refreshIfNeeded(TwitchBroadcasterToken $token, string $clientId, string $secret): bool
    {
        if ($token->expires_at->gt(now()->addMinute())) {
            return true;
        }

        $response = Http::asForm()->timeout(10)->post('https://id.twitch.tv/oauth2/token', [
            'client_id' => $clientId,
            'client_secret' => $secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
        ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            return false;
        }

        $token->update([
            'access_token' => $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token', $token->refresh_token),
            'expires_at' => now()->addSeconds((int) $response->json('expires_in', 3600)),
        ]);

        return true;
    }

    /** @return list<string>|null */
    private function chatters(TwitchBroadcasterToken $token, string $clientId): ?array
    {
        $ids = [];
        $cursor = null;

        try {
            do {
                $response = Http::withToken($token->access_token)->withHeaders(['Client-Id' => $clientId])->timeout(10)
                    ->get('https://api.twitch.tv/helix/chat/chatters', array_filter([
                        'broadcaster_id' => $token->twitch_id,
                        'moderator_id' => $token->twitch_id,
                        'first' => 1000,
                        'after' => $cursor,
                    ]))->throw();

                foreach ($response->json('data', []) as $chatter) {
                    $ids[] = (string) $chatter['user_id'];
                }
                $cursor = $response->json('pagination.cursor');
            } while ($cursor);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        return $ids;
    }

    private function award(array $twitchIds): int
    {
        $userIds = User::query()->whereIn('twitch_id', $twitchIds)->pluck('id');
        if ($userIds->isEmpty()) {
            return 0;
        }

        $points = max(0, (int) config('services.twitch.points_per_minute', 1));
        $now = now();

        return DB::transaction(function () use ($userIds, $points, $now) {
            DB::table('viewer_points')->insertOrIgnore($userIds->map(fn ($id) => [
                'user_id' => $id, 'balance' => 0, 'lifetime' => 0, 'minutes' => 0, 'created_at' => $now, 'updated_at' => $now,
            ])->all());

            // Evita pagar duas vezes no mesmo minuto se o comando rodar em duplicidade.
            return ViewerPoint::query()
                ->whereIn('user_id', $userIds)
                ->where(fn ($q) => $q->whereNull('last_awarded_at')->orWhere('last_awarded_at', '<', $now->copy()->subSeconds(50)))
                ->update([
                    'balance' => DB::raw('balance + '.$points),
                    'lifetime' => DB::raw('lifetime + '.$points),
                    'minutes' => DB::raw('minutes + 1'),
                    'last_awarded_at' => $now,
                    'updated_at' => $now,
                ]);
        });
    }
}
