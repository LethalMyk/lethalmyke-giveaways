<?php

namespace Tests\Feature;

use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\TwitchBroadcasterToken;
use App\Models\User;
use App\Models\ViewerPoint;
use App\Services\ViewerPointsAwarder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ViewerRewardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.twitch.client_id' => 'cid', 'services.twitch.client_secret' => 'sec', 'services.twitch.points_per_minute' => 2]);
    }

    private function authorizeChannel(): void
    {
        TwitchBroadcasterToken::create([
            'twitch_id' => '999', 'login' => 'lethalmyk', 'access_token' => 'a', 'refresh_token' => 'r', 'expires_at' => now()->addHour(),
        ]);
    }

    private function fakeTwitch(bool $live, array $chatters = []): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'id.twitch.tv/*' => Http::response(['access_token' => 'app']),
            'api.twitch.tv/helix/streams*' => Http::response(['data' => $live ? [['type' => 'live', 'title' => 't', 'game_name' => 'Tibia', 'viewer_count' => 1]] : []]),
            'api.twitch.tv/helix/chat/chatters*' => Http::response(['data' => array_map(fn ($id) => ['user_id' => $id], $chatters), 'pagination' => []]),
        ]);
    }

    public function test_chatters_with_linked_accounts_earn_points_only_while_live(): void
    {
        $this->authorizeChannel();
        $viewer = User::factory()->create(['twitch_id' => '111']);
        $other = User::factory()->create(['twitch_id' => '222']);

        $this->fakeTwitch(false, ['111']);
        $this->assertSame('offline', app(ViewerPointsAwarder::class)->run()['status']);
        $this->assertSame(0, ViewerPoint::count());

        Cache::flush();
        $this->fakeTwitch(true, ['111', '555']);
        $result = app(ViewerPointsAwarder::class)->run();
        $this->assertSame(['status' => 'ok', 'awarded' => 1], $result);

        $row = ViewerPoint::where('user_id', $viewer->id)->first();
        $this->assertSame([2, 2, 1], [(int) $row->balance, (int) $row->lifetime, (int) $row->minutes]);
        $this->assertNull(ViewerPoint::where('user_id', $other->id)->first());

        // segunda execução no mesmo minuto não paga de novo
        Cache::flush();
        $this->fakeTwitch(true, ['111']);
        $this->assertSame(0, app(ViewerPointsAwarder::class)->run()['awarded']);
    }

    public function test_without_authorization_nothing_is_awarded(): void
    {
        $this->assertSame('not_authorized', app(ViewerPointsAwarder::class)->run()['status']);
    }

    public function test_user_can_redeem_reward_and_admin_can_refund(): void
    {
        $user = User::factory()->create(['twitch_id' => '111']);
        ViewerPoint::create(['user_id' => $user->id, 'balance' => 100, 'lifetime' => 100, 'minutes' => 50]);
        $reward = Reward::create(['name' => 'Gift 10k', 'cost' => 80, 'stock' => 1, 'is_active' => true]);

        $this->get(route('shop.index'))->assertOk()->assertSee('Gift 10k');
    }

    public function test_redeem_checks_balance_stock_and_refund_restores(): void
    {
        $user = User::factory()->create(['twitch_id' => '111']);
        $reward = Reward::create(['name' => 'Gift 10k', 'cost' => 80, 'stock' => 1, 'is_active' => true]);

        $this->actingAs($user)->post(route('shop.redeem', $reward))->assertSessionHas('error');
        $this->assertSame(0, RewardRedemption::count());

        ViewerPoint::query()->updateOrCreate(['user_id' => $user->id], ['balance' => 100]);
        $this->actingAs($user)->post(route('shop.redeem', $reward))->assertSessionHas('success');
        $this->assertSame(20, (int) ViewerPoint::first()->balance);
        $this->assertSame(0, $reward->fresh()->stock);

        $this->actingAs($user)->post(route('shop.redeem', $reward))->assertSessionHas('error');
        $this->assertSame(1, RewardRedemption::count());

        $redemption = RewardRedemption::first();
        $this->actingAs($user)->post(route('admin.rewards.refund', $redemption))->assertForbidden();
        $this->assertSame('pending', $redemption->fresh()->status);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.rewards.refund', $redemption))->assertSessionHas('success');
        $this->assertSame(100, (int) ViewerPoint::first()->balance);
        $this->assertSame(1, $reward->fresh()->stock);
        $this->assertSame('refunded', $redemption->fresh()->status);

        $this->actingAs($admin)->get(route('admin.rewards.index'))->assertOk()->assertSee('Gift 10k');
    }
}
