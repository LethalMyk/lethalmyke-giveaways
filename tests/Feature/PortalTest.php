<?php

namespace Tests\Feature;

use App\Models\Charm;
use App\Models\Hunt;
use App\Models\HuntSuggestion;
use App\Models\Task;
use App\Models\User;
use App\Models\Vocation;
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
            '/tools/exercise-weapons-calculator',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);

            $response->assertStatus(200);
        }
    }

    public function test_hunt_places_filters_published_hunts_by_level_party_and_vocation(): void
    {
        $knight = Vocation::create(['name' => 'Knight', 'slug' => 'knight', 'is_active' => true]);
        $eligible = Hunt::create([
            'name' => 'Deep Cavern',
            'slug' => 'deep-cavern',
            'level_min' => 400,
            'level_max' => 600,
            'party_type' => 'solo',
            'is_published' => true,
            'last_reviewed_at' => now(),
            'published_at' => now(),
        ]);
        $eligible->vocations()->attach($knight);

        Hunt::create([
            'name' => 'Draft Hunt',
            'slug' => 'draft-hunt',
            'level_min' => 400,
            'party_type' => 'solo',
            'is_published' => false,
        ]);

        $this->get(route('tools.hunt-places.index', [
            'level' => 500,
            'vocation' => 'knight',
            'party_type' => 'solo',
        ]))
            ->assertOk()
            ->assertSee('Deep Cavern')
            ->assertDontSee('Draft Hunt');

        $this->get(route('tools.hunt-places.show', $eligible))
            ->assertOk()
            ->assertSee('Deep Cavern');
        $this->get(route('tools.hunt-places.show', $eligible))->assertOk();
        $this->assertDatabaseHas('hunts', ['id' => $eligible->id, 'views_count' => 1]);
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
            ->assertSee('Imbuement')
            ->assertSee('Exercise Weapons');
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

    public function test_exercise_weapon_endpoint_calculates_charges_and_duration(): void
    {
        $response = $this->postJson(route('tools.exercise-weapons.calculate'), [
            'vocation' => 'knight',
            'skill' => 'melee',
            'current_skill' => 10,
            'target_skill' => 11,
            'remaining_percent' => 100,
            'loyalty_percent' => 0,
            'dummy' => false,
            'double_event' => false,
            'price_lasting' => 10000000,
            'price_durable' => 1250000,
            'price_regular' => 347222,
        ]);

        $response->assertOk()
            ->assertJsonPath('required_points', 50)
            ->assertJsonPath('charges_required', 7)
            ->assertJsonPath('duration_seconds', 14)
            ->assertJsonPath('weapons.regular.count', 1);
    }

    public function test_exercise_weapon_endpoint_returns_validation_error_for_overflowing_ranges(): void
    {
        $this->postJson(route('tools.exercise-weapons.calculate'), [
            'vocation' => 'knight',
            'skill' => 'magic',
            'current_skill' => 10,
            'target_skill' => 200,
            'remaining_percent' => 100,
            'loyalty_percent' => 0,
            'dummy' => false,
            'double_event' => false,
            'price_lasting' => 10000000,
            'price_durable' => 1250000,
            'price_regular' => 347222,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_skill');
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

        $this->get(route('admin.dashboard'))->assertOk();

        foreach (['tasks', 'charms', 'imbuements'] as $catalog) {
            $this->get(route('admin.catalogs.index', $catalog))->assertOk();
            $this->get(route('admin.catalogs.create', $catalog))->assertOk();
        }

        $this->get(route('admin.hunts.index'))->assertOk();
        $this->get(route('admin.hunts.create'))->assertOk();
        $this->get(route('admin.vocations.index'))->assertOk();
    }

    public function test_admin_can_create_a_reviewed_hunt_with_structured_relations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $knight = Vocation::create(['name' => 'Knight', 'slug' => 'knight', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.hunts.store'), [
            'name' => 'Crystal Depths',
            'slug' => 'crystal-depths',
            'level_min' => 500,
            'party_type' => 'team',
            'minimum_party_size' => 3,
            'maximum_party_size' => 4,
            'vocations' => [$knight->id],
            'xp_min_per_hour' => 2000000,
            'xp_max_per_hour' => 3000000,
            'profit_min_per_hour' => 100000,
            'profit_max_per_hour' => 200000,
            'last_reviewed_at' => now()->format('Y-m-d H:i:s'),
            'is_published' => '1',
            'creatures_json' => json_encode([['name' => 'Crystal Golem']]),
            'requirements_json' => json_encode([['type' => 'quest', 'name' => 'Crystal Access']]),
            'supplies_json' => json_encode([['name' => 'Mana Potion', 'quantity' => 100]]),
            'loots_json' => json_encode([['name' => 'Crystal Coin', 'estimated_value' => 5000]]),
        ]);

        $response->assertRedirect(route('admin.hunts.edit', 'crystal-depths'));
        $this->assertDatabaseHas('hunts', ['slug' => 'crystal-depths', 'is_published' => true]);
        $this->assertDatabaseHas('hunt_creatures', ['name' => 'Crystal Golem']);
        $this->assertDatabaseHas('hunt_requirements', ['name' => 'Crystal Access']);
        $this->assertDatabaseHas('hunt_supplies', ['name' => 'Mana Potion', 'quantity' => 100]);
        $this->assertDatabaseHas('hunt_loots', ['name' => 'Crystal Coin', 'estimated_value' => 5000]);
        $this->assertDatabaseHas('hunt_vocation', [
            'hunt_id' => Hunt::where('slug', 'crystal-depths')->value('id'),
            'vocation_id' => $knight->id,
        ]);
    }

    public function test_admin_cannot_publish_a_hunt_without_an_explicit_review_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.hunts.create'))
            ->post(route('admin.hunts.store'), [
                'name' => 'Unreviewed Hunt',
                'level_min' => 100,
                'party_type' => 'solo',
                'is_published' => '1',
                'creatures_json' => '[]',
                'requirements_json' => '[]',
                'supplies_json' => '[]',
                'loots_json' => '[]',
            ])
            ->assertRedirect(route('admin.hunts.create'))
            ->assertSessionHasErrors('last_reviewed_at');

        $this->assertDatabaseCount('hunts', 0);
    }

    public function test_hunt_favorites_are_isolated_and_edit_suggestions_remain_pending(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $hunt = Hunt::create([
            'name' => 'Moon Temple',
            'slug' => 'moon-temple',
            'level_min' => 250,
            'party_type' => 'duo',
            'is_published' => true,
            'last_reviewed_at' => now(),
            'published_at' => now(),
        ]);

        $this->actingAs($firstUser)->post(route('tools.hunt-places.favorite', $hunt))->assertRedirect();
        $this->actingAs($secondUser)->post(route('tools.hunt-places.favorite', $hunt))->assertRedirect();

        $this->assertDatabaseHas('hunt_favorites', ['hunt_id' => $hunt->id, 'user_id' => $firstUser->id]);
        $this->assertDatabaseHas('hunt_favorites', ['hunt_id' => $hunt->id, 'user_id' => $secondUser->id]);
        $this->assertDatabaseCount('hunt_favorites', 2);

        $this->post(route('tools.hunt-places.suggestions.store', $hunt), [
            'submitter_name' => 'Rubinot player',
            'message' => 'A rota foi alterada depois da atualização do mapa.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hunt_suggestions', [
            'hunt_id' => $hunt->id,
            'status' => 'pending',
            'message' => 'A rota foi alterada depois da atualização do mapa.',
        ]);
        $this->assertNull($hunt->fresh()->description);
    }

    public function test_admin_can_moderate_a_suggestion_without_applying_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hunt = Hunt::create([
            'name' => 'Moon Temple',
            'slug' => 'moon-temple',
            'party_type' => 'duo',
            'is_published' => true,
            'last_reviewed_at' => now(),
        ]);
        $suggestion = HuntSuggestion::create([
            'hunt_id' => $hunt->id,
            'hunt_name' => $hunt->name,
            'message' => 'Atualizar a rota sul.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.hunt-suggestions.review', $suggestion), ['status' => 'reviewed'])
            ->assertRedirect(route('admin.hunts.index'));

        $this->assertDatabaseHas('hunt_suggestions', ['id' => $suggestion->id, 'status' => 'reviewed']);
        $this->assertNull($hunt->fresh()->description);
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
