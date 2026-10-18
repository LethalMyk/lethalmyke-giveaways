<?php

namespace Database\Seeders;

use App\Models\CharmDefinition;
use App\Models\CharmTier;
use App\Models\Monster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CharmCalculatorSeeder extends Seeder
{
    private const SOURCE_URL = 'https://www.tibiawiki.com.br/wiki/Charms';

    private const SOURCE_VERSION = 'TibiaWiki: versão aprovada em 23/01/2026';

    public function run(): void
    {
        DB::transaction(function (): void {
            Monster::query()->firstOrCreate(
                ['slug' => 'many-faces'],
                [
                    'name' => 'Many Faces',
                    'health' => 30000,
                    'resistances' => ['death' => 1.3],
                    'source_url' => 'https://tibia.fandom.com/wiki/Many_Faces',
                    'source_version' => 'HP: TibiaWiki Fandom; fator Death: exemplo fornecido pelo usuário',
                    'source_updated_at' => '2026-10-18 00:00:00',
                ],
            );

            foreach ($this->definitions() as $definition) {
                $tiers = $definition['tiers'];
                unset($definition['tiers']);

                $charm = CharmDefinition::query()->updateOrCreate(
                    ['slug' => $definition['slug']],
                    [
                        ...$definition,
                        'source_url' => self::SOURCE_URL,
                        'source_version' => self::SOURCE_VERSION,
                        'is_active' => true,
                    ],
                );

                foreach ($tiers as $index => $tierData) {
                    CharmTier::query()->updateOrCreate(
                        [
                            'charm_definition_id' => $charm->id,
                            'tier' => $index + 1,
                        ],
                        [
                            'activation_chance' => $tierData['chance'],
                            'effect_value' => $tierData['value'],
                            'cost' => $tierData['cost'],
                            'cost_currency' => $definition['charm_type'] === 'major'
                                ? 'charm_points'
                                : 'minor_charm_echoes',
                            'effect_config' => $tierData['config'],
                        ],
                    );
                }
            }
        });

        $this->command?->info('Configured '.CharmDefinition::query()->where('source_url', self::SOURCE_URL)->count().' charm definitions from '.self::SOURCE_VERSION.'.');
    }

    private function definitions(): array
    {
        return [
            $this->charm('carnage', 'Carnage', 'major', 'offensive', 'kill_aoe_percent', 'physical',
                'AoE após matar a criatura; 15% de dano físico baseado no HP máximo da criatura escolhida.',
                [0.10, 0.20, 0.22], [0.15, 0.15, 0.15], [600, 900, 3000],
                [['trigger' => 'kill', 'area_damage' => true], ['trigger' => 'kill', 'area_damage' => true], ['trigger' => 'kill', 'area_damage' => true]]),
            $this->charm('curse', 'Curse', 'major', 'offensive', 'elemental_percent', 'death',
                'Ataque pode causar 5% do HP máximo da criatura como dano Death.',
                [0.05, 0.10, 0.11], [0.05, 0.05, 0.05], [360, 540, 1800],
                $this->elementalConfigs()),
            $this->charm('divine-wrath', 'Divine Wrath', 'major', 'offensive', 'elemental_percent', 'holy',
                'Ataque pode causar 5% do HP máximo da criatura como dano Holy.',
                [0.05, 0.10, 0.11], [0.05, 0.05, 0.05], [600, 900, 3000],
                $this->elementalConfigs()),
            $this->charm('dodge', 'Dodge', 'major', 'defensive', 'avoid_attack', null,
                'Chance de esquivar de um ataque sem sofrer dano.',
                [0.05, 0.10, 0.11], [null, null, null], [240, 360, 1200],
                $this->configs(['metric' => 'attack_avoidance'])),
            $this->charm('enflame', 'Enflame', 'major', 'offensive', 'elemental_percent', 'fire',
                'Ataque pode causar 5% do HP máximo da criatura como dano Fire.',
                [0.05, 0.10, 0.11], [0.05, 0.05, 0.05], [400, 600, 2000],
                $this->elementalConfigs()),
            $this->charm('freeze', 'Freeze', 'major', 'offensive', 'elemental_percent', 'ice',
                'Ataque pode causar 5% do HP máximo da criatura como dano Ice.',
                [0.05, 0.10, 0.11], [0.05, 0.05, 0.05], [320, 480, 1600],
                $this->elementalConfigs()),
            $this->charm('low-blow', 'Low Blow', 'major', 'offensive', 'critical_chance_bonus', null,
                'Bônus de chance de crítico em ataques com armas críticas contra a criatura escolhida.',
                [null, null, null], [0.04, 0.08, 0.09], [800, 1200, 4000],
                $this->configs(['metric' => 'critical_chance_bonus'])),
            $this->charm('overpower', 'Overpower', 'major', 'offensive', 'character_hp_capped_percent', null,
                'Chance de causar até 5% do HP do personagem, limitada a 8% do HP máximo da criatura.',
                [0.05, 0.10, 0.11], [0.05, 0.05, 0.05], [600, 900, 3000],
                $this->configs(['base_attribute' => 'hp', 'base_fraction' => 0.05, 'monster_cap_fraction' => 0.08])),
            $this->charm('overflux', 'Overflux', 'major', 'offensive', 'character_mana_capped_percent', null,
                'Chance de causar até 2,5% da mana do personagem, limitada a 8% do HP máximo da criatura.',
                [0.05, 0.10, 0.11], [0.025, 0.025, 0.025], [600, 900, 3000],
                $this->configs(['base_attribute' => 'mana', 'base_fraction' => 0.025, 'monster_cap_fraction' => 0.08])),
            $this->charm('parry', 'Parry', 'major', 'defensive', 'reflect_incoming_damage', null,
                'Chance de refletir dano recebido; resistência da criatura ignorada e armadura considerada.',
                [0.05, 0.10, 0.11], [null, null, null], [400, 600, 2000],
                $this->configs(['metric' => 'reflected_incoming_damage', 'uses_monster_armor' => true, 'ignores_monster_resistance' => true])),
            $this->charm('poison', 'Poison', 'major', 'offensive', 'elemental_percent', 'earth',
                'Ataque pode causar 5% do HP máximo da criatura como dano Earth.',
                [0.05, 0.10, 0.11], [0.05, 0.05, 0.05], [240, 360, 1200],
                $this->elementalConfigs()),
            $this->charm('savage-blow', 'Savage Blow', 'major', 'offensive', 'critical_damage_bonus', null,
                'Bônus de dano crítico em ataques com armas críticas.',
                [null, null, null], [0.20, 0.40, 0.44], [800, 1200, 4000],
                $this->configs(['metric' => 'critical_damage_bonus'])),
            $this->charm('wound', 'Wound', 'major', 'offensive', 'elemental_percent', 'physical',
                'Ataque pode causar 5% do HP máximo da criatura como dano Physical.',
                [0.05, 0.10, 0.11], [0.05, 0.05, 0.05], [240, 360, 1200],
                $this->elementalConfigs()),
            $this->charm('zap', 'Zap', 'major', 'offensive', 'elemental_percent', 'energy',
                'Ataque pode causar 5% do HP máximo da criatura como dano Energy.',
                [0.05, 0.10, 0.11], [0.05, 0.05, 0.05], [320, 480, 1600],
                $this->elementalConfigs()),
            $this->charm('adrenaline-burst', 'Adrenaline Burst', 'minor', 'utility', 'movement_speed', null,
                'Após receber um ataque, chance de aumentar a velocidade de movimento em 150% por 10 segundos.',
                [0.06, 0.09, 0.12], [1.50, 1.50, 1.50], [100, 150, 225],
                $this->configs(['trigger' => 'received_attack', 'duration_seconds' => 10, 'metric' => 'movement_speed_bonus'])),
            $this->charm('bless', 'Bless', 'minor', 'defensive', 'death_penalty_reduction', null,
                'Reduz a perda de skills e experiência em caso de morte pela criatura escolhida.',
                [null, null, null], [0.06, 0.09, 0.12], [100, 150, 225],
                $this->configs(['trigger' => 'death', 'metric' => 'death_penalty_reduction'])),
            $this->charm('cleanse', 'Cleanse', 'minor', 'defensive', 'condition_immunity', null,
                'Após receber um ataque, chance de remover uma condição aleatória e ficar imune a ela por 11 segundos.',
                [0.06, 0.09, 0.12], [11, 11, 11], [100, 150, 225],
                $this->configs(['trigger' => 'received_attack', 'duration_seconds' => 11, 'metric' => 'condition_immunity'])),
            $this->charm('cripple', 'Cripple', 'minor', 'offensive', 'paralyze_creature', null,
                'Chance de paralisar a criatura por 10 segundos.',
                [0.06, 0.09, 0.12], [10, 10, 10], [100, 150, 225],
                $this->configs(['duration_seconds' => 10, 'metric' => 'creature_control'])),
            $this->charm('gut', 'Gut', 'minor', 'utility', 'creature_products_bonus', null,
                'Aumenta a quantidade de produtos de criaturas.',
                [null, null, null], [0.06, 0.09, 0.12], [100, 150, 225],
                $this->configs(['metric' => 'creature_products_bonus'])),
            $this->charm('numb', 'Numb', 'minor', 'defensive', 'paralyze_creature', null,
                'Após receber um ataque, chance de paralisar a criatura por 10 segundos.',
                [0.06, 0.09, 0.12], [10, 10, 10], [100, 150, 225],
                $this->configs(['trigger' => 'received_attack', 'duration_seconds' => 10, 'metric' => 'creature_control'])),
            $this->charm('scavenge', 'Scavenge', 'minor', 'utility', 'skinning_chance_bonus', null,
                'Melhora as chances de Skinning/Dusting em criaturas compatíveis.',
                [null, null, null], [0.60, 0.90, 1.20], [100, 150, 225],
                $this->configs(['metric' => 'skinning_dusting_chance_bonus'])),
            $this->charm('fatal-hold', 'Fatal Hold', 'minor', 'utility', 'prevent_creature_escape', null,
                'Chance de impedir que a criatura fuja com HP baixo por 30 segundos.',
                [0.30, 0.45, 0.60], [30, 30, 30], [100, 150, 225],
                $this->configs(['duration_seconds' => 30, 'metric' => 'prevent_creature_escape'])),
            $this->charm('vampiric-embrace', 'Vampiric Embrace', 'minor', 'utility', 'life_leech_bonus', null,
                'Bônus de Life Leech quando o equipamento possui essa função.',
                [null, null, null], [0.016, 0.024, 0.032], [100, 150, 225],
                $this->configs(['metric' => 'life_leech_bonus'])),
            $this->charm('void-inversion', 'Void Inversion', 'minor', 'defensive', 'mana_drain_inversion', null,
                'Chance de ganhar mana em vez de perdê-la em ataques de Mana Drain.',
                [0.20, 0.30, 0.40], [null, null, null], [100, 150, 225],
                $this->configs(['metric' => 'mana_drain_inversion'])),
            $this->charm('voids-call', "Void's Call", 'minor', 'utility', 'mana_leech_bonus', null,
                'Bônus de Mana Leech quando o equipamento possui essa função.',
                [null, null, null], [0.008, 0.012, 0.016], [100, 150, 225],
                $this->configs(['metric' => 'mana_leech_bonus'])),
        ];
    }

    private function charm(
        string $slug,
        string $name,
        string $type,
        string $category,
        string $effectType,
        ?string $element,
        string $description,
        array $chances,
        array $values,
        array $costs,
        array $configs,
    ): array {
        return [
            'slug' => $slug,
            'name' => $name,
            'charm_type' => $type,
            'category' => $category,
            'effect_type' => $effectType,
            'element' => $element,
            'description' => $description,
            'tiers' => array_map(fn (int $index): array => [
                'chance' => $chances[$index],
                'value' => $values[$index],
                'cost' => $costs[$index],
                'config' => $configs[$index],
            ], range(0, 2)),
        ];
    }

    private function elementalConfigs(): array
    {
        return $this->configs(['base_fraction' => 0.05]);
    }

    private function configs(array $config): array
    {
        return [$config, $config, $config];
    }
}
