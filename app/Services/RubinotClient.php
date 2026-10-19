<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class RubinotClient
{
    /**
     * @return array{status: 'ok', data: array{name: string, level: int, vocation: string, world: string}}|array{status: 'not_found'}|array{status: 'unavailable'}
     */
    public function character(string $name): array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'Mozilla/5.0 (compatible; LethalMykeTools/1.0)'])
                ->get(rtrim(config('services.rubinot.url', 'https://rubinot.com.br'), '/').'/api/characters/search', ['name' => $name]);
        } catch (Throwable) {
            return ['status' => 'unavailable'];
        }

        $json = $response->json();

        // Respostas HTML (ex.: desafio do Cloudflare) não são JSON e indicam bloqueio.
        if (! is_array($json)) {
            return ['status' => 'unavailable'];
        }

        $player = $json['player'] ?? null;

        if (! is_array($player) || blank($player['name'] ?? null)) {
            return $response->successful() || $response->status() === 404
                ? ['status' => 'not_found']
                : ['status' => 'unavailable'];
        }

        return ['status' => 'ok', 'data' => [
            'name' => (string) $player['name'],
            'level' => (int) ($player['level'] ?? 0),
            'vocation' => (string) ($player['vocation'] ?? ''),
            'world' => (string) ($player['world'] ?? ''),
        ]];
    }
}
