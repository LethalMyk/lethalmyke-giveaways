<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class TwitchLiveStatus
{
    public function channel(): string
    {
        return (string) config('services.twitch.channel_login', 'lethalmyk');
    }

    public function url(): string
    {
        return 'https://www.twitch.tv/'.$this->channel();
    }

    /** @return array{live: bool, title?: string, game?: string, viewers?: int} */
    public function status(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('twitch.live.'.$this->channel());
        }

        return Cache::remember('twitch.live.'.$this->channel(), 60, fn () => $this->fetch());
    }

    private function fetch(): array
    {
        $clientId = config('services.twitch.client_id');
        $secret = config('services.twitch.client_secret');

        if (! $clientId || ! $secret) {
            return ['live' => false];
        }

        try {
            $token = Cache::remember('twitch.app_token', 3000, function () use ($clientId, $secret) {
                return Http::asForm()->timeout(5)->post('https://id.twitch.tv/oauth2/token', [
                    'client_id' => $clientId,
                    'client_secret' => $secret,
                    'grant_type' => 'client_credentials',
                ])->throw()->json('access_token');
            });

            $response = Http::withToken($token)->withHeaders(['Client-Id' => $clientId])->timeout(5)
                ->get('https://api.twitch.tv/helix/streams', ['user_login' => $this->channel()]);

            if ($response->status() === 401) {
                Cache::forget('twitch.app_token');
            }

            $stream = $response->throw()->json('data.0');
        } catch (Throwable $e) {
            report($e);

            return ['live' => false];
        }

        if (! $stream || ($stream['type'] ?? '') !== 'live') {
            return ['live' => false];
        }

        return [
            'live' => true,
            'title' => (string) ($stream['title'] ?? ''),
            'game' => (string) ($stream['game_name'] ?? ''),
            'viewers' => (int) ($stream['viewer_count'] ?? 0),
        ];
    }
}
