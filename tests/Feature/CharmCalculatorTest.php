<?php

namespace Tests\Feature;

use App\Models\CharmDefinition;
use App\Models\CharmTier;
use App\Models\Hunt;
use App\Models\Monster;
use App\Models\User;
use Database\Seeders\CharmCalculatorSeeder;
use Database\Seeders\HuntCreatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CharmCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private Monster $manyFaces;

    private CharmDefinition $curse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CharmCalculatorSeeder::class);
        $this->manyFaces = Monster::query()->where('slug', 'many-faces')->firstOrFail();
        $this->curse = CharmDefinition::query()->where('slug', 'curse')->firstOrFail();
    }

    public function test_phpunit_uses_an_in_memory_database(): void
    {
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
    }

    public function test_curse_tier_two_calculates_expected_damage_against_many_faces(): void
    {
        $response = $this->postJson(route('tools.charm-calculator.calculate'), $this->payload([
            ['monster_slug' => $this->manyFaces->slug, 'kills' => 30],
        ]));

        $response->assertOk()
            ->assertJsonPath('results.0.recommended.base_damage', 1500)
            ->assertJsonPath('results.0.recommended.damage_per_activation', 1950)
            ->assertJsonPath('results.0.recommended.expected_damage_per_eligible_attack', 195);
    }

    public function test_inventory_tiers_are_respected_and_hunt_preset_unions_recommendations(): void
    {
        $otherMonster = Monster::query()->create([
            'slug' => 'holy-weak-fixture',
            'name' => 'Holy Weak Fixture',
            'health' => 20000,
            'resistances' => ['death' => 0.5, 'holy' => 2],
        ]);

        $payload = $this->payload([
            ['monster_slug' => $this->manyFaces->slug, 'kills' => 30],
            ['monster_slug' => $otherMonster->slug, 'kills' => 10],
        ]);
        $payload['charms'] = [
            ['charm_slug' => 'curse', 'tier' => 2],
            ['charm_slug' => 'divine-wrath', 'tier' => 1],
        ];

        $this->postJson(route('tools.charm-calculator.calculate'), $payload)
            ->assertOk()
            ->assertJsonPath('results.0.recommended.charm_slug', 'curse')
            ->assertJsonPath('results.0.recommended.tier', 2)
            ->assertJsonPath('results.1.recommended.charm_slug', 'divine-wrath')
            ->assertJsonPath('results.1.recommended.tier', 1)
            ->assertJsonCount(2, 'hunt_preset')
            ->assertJsonPath('hunt_preset.0.tier', 2)
            ->assertJsonPath('hunt_preset.1.tier', 1);
    }

    public function test_each_charm_is_assigned_to_a_single_creature(): void
    {
        $weaker = Monster::query()->create([
            'slug' => 'weaker-death-fixture',
            'name' => 'Weaker Death Fixture',
            'health' => 30000,
            'resistances' => ['death' => 1.1, 'holy' => 1.0],
        ]);

        $payload = $this->payload([
            ['monster_slug' => $this->manyFaces->slug, 'kills' => 100],
            ['monster_slug' => $weaker->slug, 'kills' => 100],
        ]);
        $payload['charms'] = [
            ['charm_slug' => 'curse', 'tier' => 2],
            ['charm_slug' => 'divine-wrath', 'tier' => 1],
        ];

        $response = $this->postJson(route('tools.charm-calculator.calculate'), $payload)->assertOk();
        $bySlug = collect($response->json('results'))->keyBy('monster.slug');

        $this->assertSame('curse', $bySlug[$this->manyFaces->slug]['recommended']['charm_slug']);
        $this->assertSame('divine-wrath', $bySlug[$weaker->slug]['recommended']['charm_slug']);
        $assigned = collect($response->json('results'))->flatMap(fn ($row) => collect($row['recommended_combination'])->pluck('charm_slug'));
        $this->assertSame($assigned->count(), $assigned->unique()->count());

        $payload['charms'] = [['charm_slug' => 'curse', 'tier' => 2]];
        $response = $this->postJson(route('tools.charm-calculator.calculate'), $payload)->assertOk();
        $this->assertCount(1, $response->json('hunt_preset'));
        $this->assertSame(1, collect($response->json('results'))->filter(fn ($row) => $row['recommended'] !== null)->count());

        $payload['monsters'] = [
            ['monster_slug' => $this->manyFaces->slug, 'kills' => 5],
            ['monster_slug' => $weaker->slug, 'kills' => 50],
        ];
        $payload['criterion'] = 'hunt';
        $response = $this->postJson(route('tools.charm-calculator.calculate'), $payload)->assertOk();
        $response->assertJsonPath('results.0.monster.slug', $weaker->slug)
            ->assertJsonPath('results.0.recommended.charm_slug', 'curse')
            ->assertJsonPath('results.1.recommended', null);
    }

    public function test_hunt_import_orders_creatures_by_spawn_count(): void
    {
        $hunt = Hunt::create(['name' => 'Dark Thais', 'slug' => 'dark-thais', 'is_published' => true]);
        $this->seed(HuntCreatureSeeder::class);
        $hunt->creatureEntries()->where('name', 'Many Faces')->update(['spawn_count' => 20]);
        $hunt->creatureEntries()->where('name', 'Distorted Phantom')->update(['spawn_count' => 90]);

        $this->getJson(route('tools.charm-calculator.hunt-creatures', $hunt))
            ->assertOk()
            ->assertJsonPath('monsters.0.slug', 'distorted-phantom')
            ->assertJsonPath('monsters.0.spawn_count', 90)
            ->assertJsonPath('monsters.1.slug', 'many-faces');
    }

    public function test_free_account_major_capacity_is_applied_across_the_hunt(): void
    {
        $monsters = collect([
            ['death' => 2, 'holy' => 0.5, 'ice' => 0.5],
            ['death' => 0.5, 'holy' => 2, 'ice' => 0.5],
            ['death' => 0.5, 'holy' => 0.5, 'ice' => 2],
        ])->map(fn (array $resistances, int $index): array => [
            'monster' => Monster::query()->create([
                'slug' => "multi-element-fixture-{$index}",
                'name' => "Multi Element Fixture {$index}",
                'health' => 20000,
                'resistances' => $resistances,
            ]),
            'kills' => 1,
        ]);
        $payload = $this->payload(
            $monsters->map(fn (array $entry): array => [
                'monster_slug' => $entry['monster']->slug,
                'kills' => $entry['kills'],
            ])->all(),
            ['hp' => 1000, 'mana' => 1000, 'account_type' => 'free'],
        );
        $payload['charms'] = [
            ['charm_slug' => 'curse', 'tier' => 2],
            ['charm_slug' => 'divine-wrath', 'tier' => 2],
            ['charm_slug' => 'freeze', 'tier' => 2],
        ];

        $this->postJson(route('tools.charm-calculator.calculate'), $payload)
            ->assertOk()
            ->assertJsonPath('major_charm_capacity', 2)
            ->assertJsonCount(2, 'hunt_preset');
    }

    public function test_calculation_and_preset_require_explicit_inventory_confirmation(): void
    {
        $payload = $this->payload([
            ['monster_slug' => $this->manyFaces->slug, 'kills' => 1],
        ]);
        $payload['inventory_confirmed'] = false;

        $this->postJson(route('tools.charm-calculator.calculate'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('inventory_confirmed');

        $this->actingAs(User::factory()->create())
            ->postJson(route('tools.charm-calculator.presets.store'), [
                'name' => 'Unconfirmed',
                'configuration' => $payload,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('configuration.inventory_confirmed');
    }

    public function test_unknown_elemental_resistance_is_not_assumed_to_be_neutral(): void
    {
        $monster = Monster::query()->create([
            'slug' => 'unknown-resistance-fixture',
            'name' => 'Unknown Resistance Fixture',
            'health' => 20000,
            'resistances' => [],
        ]);

        $response = $this->postJson(route('tools.charm-calculator.calculate'), $this->payload([
            ['monster_slug' => $monster->slug, 'kills' => 1],
        ]));

        $response->assertOk()
            ->assertJsonPath('results.0.recommended', null)
            ->assertJsonPath('results.0.alternatives.0.calculable', false)
            ->assertJsonPath('results.0.alternatives.0.limitation', 'Resistência deste elemento não disponível para esta criatura.');
    }

    public function test_character_and_charm_tier_inputs_are_validated(): void
    {
        $this->postJson(route('tools.charm-calculator.calculate'), $this->payload(
            [['monster_slug' => $this->manyFaces->slug, 'kills' => 1]],
            ['hp' => 0, 'mana' => 100],
        ))->assertUnprocessable()
            ->assertJsonValidationErrors('character.hp');

        $this->postJson(route('tools.charm-calculator.calculate'), [
            ...$this->payload([['monster_slug' => $this->manyFaces->slug, 'kills' => 1]]),
            'charms' => [['charm_slug' => $this->curse->slug, 'tier' => 4]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('charms.0.tier');
    }

    public function test_creature_search_uses_local_data_and_exposes_slugs_not_internal_ids(): void
    {
        $this->getJson(route('tools.charm-calculator.creatures', ['q' => 'Many Faces']))
            ->assertOk()
            ->assertJsonPath('0.slug', 'many-faces')
            ->assertJsonMissingPath('0.id');
    }

    public function test_multiple_creatures_are_compared_and_kills_are_only_a_weight(): void
    {
        $otherMonster = Monster::query()->create([
            'slug' => 'death-resistant-fixture',
            'name' => 'Death Resistant Fixture',
            'health' => 20000,
            'resistances' => ['death' => 0.5],
        ]);

        $response = $this->postJson(route('tools.charm-calculator.calculate'), $this->payload([
            ['monster_slug' => $this->manyFaces->slug, 'kills' => 30],
            ['monster_slug' => $otherMonster->slug, 'kills' => 10],
        ], criterion: 'hunt'));

        $response->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.monster.name', 'Many Faces')
            ->assertJsonPath('results.0.weighted_expected_damage', 5850)
            ->assertJsonPath('results.1.weighted_expected_damage', 0)
            ->assertJsonPath('results.1.recommended', null)
            ->assertJsonPath('total_expected_hunt_damage', null);
    }

    public function test_preset_is_private_to_its_owner_and_share_link_is_revocable(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $preset = $this->actingAs($owner)->postJson(
            route('tools.charm-calculator.presets.store'),
            [
                'name' => 'Many Faces',
                'configuration' => $this->payload([
                    ['monster_slug' => $this->manyFaces->slug, 'kills' => 30],
                ]),
            ],
        )->assertCreated()->json('preset');

        $this->actingAs($otherUser)
            ->getJson(route('tools.charm-calculator.presets.show', $preset['id']))
            ->assertNotFound();

        $this->actingAs($owner)
            ->getJson(route('tools.charm-calculator.presets.show', $preset['id']))
            ->assertOk()
            ->assertJsonPath('preset.configuration.monsters.0.monster_slug', 'many-faces')
            ->assertJsonPath('preset.configuration.hunt_preset.0.charm_slug', 'curse')
            ->assertJsonPath('preset.configuration.hunt_preset.0.tier', 2);

        $this->actingAs($owner)
            ->putJson(route('tools.charm-calculator.presets.update', $preset['id']), [
                'name' => 'Updated preset',
                'configuration' => $this->payload([
                    ['monster_slug' => $this->manyFaces->slug, 'kills' => 25],
                ]),
            ])
            ->assertOk()
            ->assertJsonPath('preset.name', 'Updated preset');

        $shareUrl = $this->actingAs($owner)
            ->postJson(route('tools.charm-calculator.presets.share', $preset['id']))
            ->assertOk()
            ->json('url');

        $this->get($shareUrl)->assertOk()->assertSee('Many Faces');

        $this->actingAs($owner)
            ->deleteJson(route('tools.charm-calculator.presets.share.destroy', $preset['id']))
            ->assertOk();

        $this->get($shareUrl)->assertNotFound();
    }

    public function test_guest_can_calculate_but_cannot_save_presets(): void
    {
        $this->get(route('tools.charm-calculator'))->assertOk();
        $this->postJson(route('tools.charm-calculator.calculate'), $this->payload([
            ['monster_slug' => $this->manyFaces->slug, 'kills' => 1],
        ]))->assertOk();

        $this->post(route('tools.charm-calculator.presets.store'), [
            'name' => 'Guest config',
            'configuration' => $this->payload([
                ['monster_slug' => $this->manyFaces->slug, 'kills' => 1],
            ]),
        ])->assertRedirect(route('home'));
    }

    public function test_reference_seeder_is_idempotent(): void
    {
        CharmTier::query()
            ->where('charm_definition_id', $this->curse->id)
            ->where('tier', 1)
            ->update(['activation_chance' => 0.5]);
        Artisan::call('db:seed', ['--class' => CharmCalculatorSeeder::class, '--no-interaction' => true]);
        Artisan::call('db:seed', ['--class' => CharmCalculatorSeeder::class, '--no-interaction' => true]);

        $this->assertSame(1, Monster::query()->where('slug', 'many-faces')->count());
        $this->assertSame(25, CharmDefinition::query()->count());
        $this->assertSame(75, CharmTier::query()->count());
        $this->assertSame(3, CharmTier::query()->where('charm_definition_id', $this->curse->id)->count());
        $this->assertSame('charm_points', $this->curse->tiers()->first()->cost_currency);
        $this->assertSame(0.05, (float) $this->curse->tiers()->where('tier', 1)->value('activation_chance'));
        $this->assertSame(0.1, (float) $this->curse->tiers()->where('tier', 2)->value('activation_chance'));
        $this->assertSame('minor', CharmDefinition::query()->where('slug', 'gut')->value('charm_type'));
    }

    public function test_overpower_and_overflux_use_character_attributes_and_monster_hp_cap(): void
    {
        $overpower = CharmDefinition::query()->where('slug', 'overpower')->firstOrFail();
        $overflux = CharmDefinition::query()->where('slug', 'overflux')->firstOrFail();

        $this->postJson(route('tools.charm-calculator.calculate'), [
            'character' => ['hp' => 100000, 'mana' => 100000, 'account_type' => 'premium'],
            'monsters' => [['monster_slug' => $this->manyFaces->slug, 'kills' => 1]],
            'charms' => [
                ['charm_slug' => $overpower->slug, 'tier' => 2],
                ['charm_slug' => $overflux->slug, 'tier' => 3],
            ],
            'criterion' => 'individual',
            'inventory_confirmed' => true,
        ])->assertOk()
            ->assertJsonPath('results.0.alternatives.0.charm_slug', 'overflux')
            ->assertJsonPath('results.0.alternatives.0.damage_per_activation', 2400)
            ->assertJsonPath('results.0.alternatives.0.expected_damage_per_eligible_attack', 264)
            ->assertJsonPath('results.0.alternatives.1.charm_slug', 'overpower')
            ->assertJsonPath('results.0.alternatives.1.damage_per_activation', 2400)
            ->assertJsonPath('results.0.alternatives.1.expected_damage_per_eligible_attack', 240);
    }

    public function test_hunt_creatures_are_imported_from_seeded_json_and_published_hunts_only(): void
    {
        $hunt = Hunt::create(['name' => 'Dark Thais', 'slug' => 'dark-thais', 'is_published' => true]);
        $draft = Hunt::create(['name' => 'Draft', 'slug' => 'draft', 'is_published' => false]);
        $hunt->creatureEntries()->create(['name' => 'Unknown Beast', 'display_order' => 99]);

        $this->seed(HuntCreatureSeeder::class);
        $this->seed(HuntCreatureSeeder::class);

        $this->assertSame(8, $hunt->creatureEntries()->count());
        $this->assertSame(7, Monster::where('slug', 'like', '%apparition')->orWhere('slug', 'distorted-phantom')->orWhere('slug', 'many-faces')->count());

        $response = $this->getJson(route('tools.charm-calculator.hunt-creatures', $hunt))->assertOk();
        $response->assertJsonCount(7, 'monsters')
            ->assertJsonPath('missing.0', 'Unknown Beast')
            ->assertJsonPath('monsters.0.slug', 'distorted-phantom')
            ->assertJsonPath('monsters.0.resistances.earth', 0.5);

        $this->getJson(route('tools.charm-calculator.hunt-creatures', $draft))->assertNotFound();
        $this->get(route('tools.charm-calculator'))->assertSee('Dark Thais (8)');

        $hunt->update(['last_reviewed_at' => now(), 'published_at' => now()]);
        $this->get(route('tools.hunt-places.show', $hunt))->assertOk()->assertSee('30.000 HP')->assertSee('130%');
    }

    private function payload(array $monsters, array $character = ['hp' => 1000, 'mana' => 1000, 'account_type' => 'premium'], string $criterion = 'individual'): array
    {
        return [
            'character' => $character,
            'monsters' => $monsters,
            'charms' => [['charm_slug' => $this->curse->slug, 'tier' => 2]],
            'criterion' => $criterion,
            'inventory_confirmed' => true,
        ];
    }
}
