<?php

namespace Tests\Feature;

use App\Models\Charm;
use App\Models\Task;
use App\Models\User;
use App\Services\TibiaCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_tools_are_accessible(): void
    {
        $routes = [
            '/',
            '/tools/map-task',
            '/tools/hunt-places',
            '/tools/charm-calculator',
            '/tools/imbuement-calculator',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);

            $response->assertStatus(200);
        }
    }

    public function test_shared_layout_loads_frontend_assets_and_tool_navigation(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('/build/assets/app-', false)
            ->assertSee('--neon-purple')
            ->assertSee('--neon-cyan')
            ->assertSee('Map Task')
            ->assertSee('Hunt Places')
            ->assertSee('Charm')
            ->assertSee('Imbuement');
    }

    public function test_charm_calculator_computes_missing_points(): void
    {
        $result = TibiaCalculator::calculateCharm([
            ['name' => 'Charm A', 'cost' => 40],
            ['name' => 'Charm B', 'cost' => 25],
        ], 50);

        $this->assertSame(65, $result['required']);
        $this->assertSame(-15, $result['difference']);
        $this->assertSame(15, $result['missing']);
        $this->assertFalse($result['is_enough']);
    }

    public function test_imbuement_calculator_computes_total_cost(): void
    {
        $result = TibiaCalculator::calculateImbuement([
            ['name' => 'Crystal', 'quantity' => 2, 'unit_price' => 150],
            ['name' => 'Ore', 'quantity' => 1, 'unit_price' => 250],
        ]);

        $this->assertSame(550, $result['total']);
        $this->assertSame(300, $result['breakdown'][0]['subtotal']);
        $this->assertSame(250, $result['breakdown'][1]['subtotal']);
    }

    public function test_admin_can_create_a_charm_catalog_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.catalogs.store', 'charms'), [
            'name' => 'Dodge',
            'cost' => 600,
            'group_name' => 'Charm',
            'description' => 'Reduz o dano recebido.',
        ]);

        $response->assertRedirect(route('admin.catalogs.index', 'charms'));
        $this->assertDatabaseHas('charms', ['name' => 'Dodge', 'cost' => 600]);
    }

    public function test_non_admin_cannot_manage_catalogs(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.catalogs.index', 'charms'))
            ->assertForbidden();

        $this->assertDatabaseCount('charms', 0);
    }

    public function test_admin_can_open_catalog_management_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        foreach (['tasks', 'hunts', 'charms', 'imbuements'] as $catalog) {
            $this->get(route('admin.catalogs.index', $catalog))->assertOk();
            $this->get(route('admin.catalogs.create', $catalog))->assertOk();
        }
    }

    public function test_user_can_save_private_task_progress(): void
    {
        $user = User::factory()->create();
        $task = Task::create([
            'name' => 'Trolls',
            'required_quantity' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('tools.map-task.progress', $task), ['kills' => 12])
            ->assertRedirect(route('tools.map-task'));

        $this->assertDatabaseHas('user_task_progress', [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'kills' => 12,
            'completed' => false,
        ]);
    }

    public function test_task_progress_is_scoped_to_the_authenticated_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $task = Task::create([
            'name' => 'Trolls',
            'required_quantity' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($firstUser)->post(route('tools.map-task.progress', $task), ['kills' => 7]);
        $this->actingAs($secondUser)->post(route('tools.map-task.progress', $task), ['kills' => 4]);

        $this->assertDatabaseHas('user_task_progress', [
            'user_id' => $firstUser->id,
            'task_id' => $task->id,
            'kills' => 7,
        ]);
        $this->assertDatabaseHas('user_task_progress', [
            'user_id' => $secondUser->id,
            'task_id' => $task->id,
            'kills' => 4,
        ]);
        $this->assertDatabaseCount('user_task_progress', 2);

        $this->actingAs($firstUser)
            ->get(route('tools.map-task'))
            ->assertOk()
            ->assertSee('7 / 20')
            ->assertDontSee('4 / 20');

        $this->actingAs($secondUser)
            ->get(route('tools.map-task'))
            ->assertOk()
            ->assertSee('4 / 20')
            ->assertDontSee('7 / 20');
    }

    public function test_task_progress_cannot_exceed_the_required_quantity(): void
    {
        $user = User::factory()->create();
        $task = Task::create([
            'name' => 'Trolls',
            'required_quantity' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->from(route('tools.map-task'))
            ->post(route('tools.map-task.progress', $task), ['kills' => 21, 'task_id' => $task->id])
            ->assertRedirect(route('tools.map-task'))
            ->assertSessionHasErrors('kills');

        $this->assertDatabaseCount('user_task_progress', 0);
    }

    public function test_twitch_callback_creates_an_account_and_grants_configured_admin_access(): void
    {
        config()->set('services.twitch.admin_ids', ['twitch-owner-42']);
        $this->mockTwitchCallback('twitch-owner-42', 'Lethal Myke');

        $this->get(route('twitch.callback'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'twitch_id' => 'twitch-owner-42',
            'twitch_username' => 'lethalmyke',
            'role' => 'admin',
        ]);
        $this->assertAuthenticated();
    }

    public function test_twitch_callback_links_to_the_authenticated_account(): void
    {
        $user = User::factory()->create();
        $this->mockTwitchCallback('twitch-user-15', 'Player One');

        $this->actingAs($user)
            ->withSession(['twitch_link_user_id' => $user->id])
            ->get(route('twitch.callback'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'twitch_id' => 'twitch-user-15',
            'twitch_username' => 'playerone',
        ]);
    }

    public function test_twitch_account_already_owned_by_another_user_cannot_be_linked(): void
    {
        $owner = User::factory()->create(['twitch_id' => 'twitch-user-15']);
        $account = User::factory()->create();
        $this->mockTwitchCallback('twitch-user-15', 'Player One');

        $this->actingAs($account)
            ->withSession(['twitch_link_user_id' => $account->id])
            ->get(route('twitch.callback'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'twitch_id' => 'twitch-user-15']);
        $this->assertDatabaseHas('users', ['id' => $account->id, 'twitch_id' => null]);
    }

    public function test_twitch_link_route_redirects_guests_to_the_dashboard(): void
    {
        $this->get(route('twitch.link'))->assertRedirect(route('home'));
    }

    private function mockTwitchCallback(string $id, string $name): void
    {
        $twitchUser = \Mockery::mock();
        $twitchUser->shouldReceive('getId')->andReturn($id);
        $twitchUser->shouldReceive('getName')->andReturn($name);
        $twitchUser->shouldReceive('getNickname')->andReturn(strtolower(str_replace(' ', '', $name)));
        $twitchUser->shouldReceive('getAvatar')->andReturn('https://example.test/avatar.png');

        $driver = \Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($twitchUser);

        Socialite::shouldReceive('driver')->once()->with('twitch')->andReturn($driver);
    }
}
