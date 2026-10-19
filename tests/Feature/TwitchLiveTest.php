<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwitchLiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.twitch.client_id' => 'cid', 'services.twitch.client_secret' => 'sec']);
    }

    public function test_shows_live_badge_when_channel_is_online(): void
    {
        Http::fake([
            'id.twitch.tv/*' => Http::response(['access_token' => 'tok']),
            'api.twitch.tv/helix/streams*' => Http::response(['data' => [['type' => 'live', 'title' => 'Hunt', 'game_name' => 'Tibia', 'viewer_count' => 5]]]),
        ]);

        $this->get(route('twitch.live'))->assertJson(['live' => true, 'game' => 'Tibia']);
        $this->get(route('home'))->assertSee('https://www.twitch.tv/lethalmyk', false)->assertSee('Ao vivo');
    }

    public function test_offline_or_failure_reports_not_live(): void
    {
        Http::fake(['id.twitch.tv/*' => Http::response(['access_token' => 'tok']), 'api.twitch.tv/helix/streams*' => Http::response(['data' => []])]);
        $this->get(route('twitch.live'))->assertJson(['live' => false]);

        Cache::flush();
        Http::fake(['*' => Http::response('err', 500)]);
        $this->get(route('twitch.live'))->assertJson(['live' => false]);
    }
}
