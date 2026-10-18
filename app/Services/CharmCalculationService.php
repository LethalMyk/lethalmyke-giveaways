<?php

namespace App\Services;

use App\Models\CharmDefinition;
use App\Models\CharmTier;
use App\Models\Monster;
use Illuminate\Support\Collection;

class CharmCalculationService
{
    private const ELEMENTAL_PERCENT = 'elemental_percent';

    public function loadSelected(array $selectedCharms): Collection
    {
        return CharmDefinition::query()
            ->whereIn('slug', collect($selectedCharms)->pluck('charm_slug')->all())
            ->where('is_active', true)
            ->with('tiers')
            ->orderBy('name')
            ->get();
    }

    public function compare(
        Monster $monster,
        array $selectedCharms,
        array $character = [],
        ?Collection $definitions = null,
    ): array {
        $selectedTiers = collect($selectedCharms)->keyBy('charm_slug');
        $charms = $definitions ?? $this->loadSelected($selectedCharms);
        $alternatives = [];

        foreach ($charms as $charm) {
            $selectedTier = $selectedTiers->get($charm->slug)['tier'];
            $tier = $charm->tiers->firstWhere('tier', $selectedTier);

            if ($tier) {
                $alternatives[] = $this->calculate($monster, $charm, $tier, $character);
            }
        }

        usort($alternatives, static fn (array $left, array $right): int =>
            ($right['expected_damage_per_eligible_attack'] ?? -1)
                <=> ($left['expected_damage_per_eligible_attack'] ?? -1));

        $combination = collect($alternatives)
            ->filter(fn (array $alternative): bool => $alternative['calculable'])
            ->groupBy('charm_type')
            ->flatMap(function (Collection $group, string $type): Collection {
                $limit = (int) config("charms.max_active_{$type}_per_monster", 1);

                return $group->take(max(0, $limit));
            })
            ->values()
            ->all();

        return [
            'monster' => [
                'slug' => $monster->slug,
                'name' => $monster->name,
                'health' => $monster->health,
                'resistances' => $monster->resistances ?? [],
                'source_url' => $monster->source_url,
            ],
            'recommended' => collect($alternatives)->first(fn (array $alternative): bool => $alternative['calculable']),
            'recommended_combination' => $combination,
            'configured_charm_slots' => [
                'major' => (int) config('charms.max_active_major_per_monster', 1),
                'minor' => (int) config('charms.max_active_minor_per_monster', 1),
            ],
            'alternatives' => $alternatives,
            'aggregate_note' => 'Dano total da hunt não calculado: faltam ataques elegíveis por criatura. Kills são usados apenas como peso comparativo.',
        ];
    }

    public function calculate(
        Monster $monster,
        CharmDefinition $charm,
        CharmTier $tier,
        array $character = [],
    ): array {
        $result = [
            'charm' => $charm->name,
            'charm_slug' => $charm->slug,
            'charm_type' => $charm->charm_type,
            'tier' => $tier->tier,
            'effect_type' => $charm->effect_type,
            'element' => $charm->element,
            'activation_chance' => $tier->activation_chance,
            'effect_value' => $tier->effect_value,
            'effect_summary' => $this->effectSummary($charm, $tier),
            'cost' => $tier->cost,
            'cost_currency' => $tier->cost_currency,
            'calculable' => false,
            'limitation' => null,
            'expected_damage_per_eligible_attack' => null,
        ];

        if ($charm->effect_type === self::ELEMENTAL_PERCENT) {
            return $this->calculateElemental($monster, $charm, $tier, $result);
        }

        if (in_array($charm->effect_type, ['character_hp_capped_percent', 'character_mana_capped_percent'], true)) {
            return $this->calculateCharacterDamage($monster, $charm, $tier, $result, $character);
        }

        $result['limitation'] = $tier->activation_chance === null
            ? 'Este efeito não possui dano direto; a métrica adequada está registrada no catálogo.'
            : 'Chance registrada; este efeito não é convertido em dano direto.';

        return $result;
    }

    private function calculateElemental(
        Monster $monster,
        CharmDefinition $charm,
        CharmTier $tier,
        array $result,
    ): array {
        $baseFraction = $tier->effect_config['base_fraction'] ?? null;
        if ($tier->activation_chance === null || $baseFraction === null) {
            $result['limitation'] = 'Dados de chance ou dano-base não disponíveis para este tier.';

            return $result;
        }

        if ($tier->activation_chance < 0 || $tier->activation_chance > 1) {
            $result['limitation'] = 'Chance fora do intervalo suportado.';

            return $result;
        }

        if (blank($charm->element)) {
            $result['limitation'] = 'Elemento não definido para este charm.';

            return $result;
        }

        $resistanceMultiplier = ($monster->resistances ?? [])[$charm->element] ?? null;
        if ($resistanceMultiplier === null) {
            $result['limitation'] = 'Resistência deste elemento não disponível para esta criatura.';

            return $result;
        }

        if ($baseFraction <= 0 || $baseFraction > 1 || $resistanceMultiplier < 0) {
            $result['limitation'] = 'Os valores de dano-base ou resistência estão fora do intervalo suportado.';

            return $result;
        }

        $baseDamage = $monster->health * $baseFraction;
        $damagePerActivation = $baseDamage * $resistanceMultiplier;
        $result['resistance_multiplier'] = $resistanceMultiplier;
        $result['base_damage'] = $baseDamage;
        $result['damage_per_activation'] = $damagePerActivation;
        $result['expected_damage_per_eligible_attack'] = $damagePerActivation * $tier->activation_chance;
        $result['explanation'] = sprintf(
            'HP × %.1f%% × resistência (%.0f%%) × chance (%.1f%%).',
            $baseFraction * 100,
            $resistanceMultiplier * 100,
            $tier->activation_chance * 100,
        );

        return $this->markCalculable($result);
    }

    private function calculateCharacterDamage(
        Monster $monster,
        CharmDefinition $charm,
        CharmTier $tier,
        array $result,
        array $character,
    ): array {
        $config = $tier->effect_config ?? [];
        $fraction = $config['base_fraction'] ?? null;
        $attribute = $config['base_attribute'] ?? null;
        $chance = $tier->activation_chance;

        if ($chance === null || $fraction === null || $attribute === null || ! isset($character[$attribute])) {
            $result['limitation'] = 'Informe o atributo do personagem e confirme os dados do efeito para calcular.';

            return $result;
        }

        if ($chance < 0 || $chance > 1 || $fraction <= 0 || $fraction > 1) {
            $result['limitation'] = 'Os valores de chance ou efeito estão fora do intervalo suportado.';

            return $result;
        }

        $capFraction = $config['monster_cap_fraction'] ?? null;
        if ($capFraction === null || $capFraction <= 0 || $capFraction > 1) {
            $result['limitation'] = 'Limite de dano por criatura não está configurado corretamente.';

            return $result;
        }

        $baseDamage = $character[$attribute] * $fraction;
        $damagePerActivation = min($baseDamage, $monster->health * $capFraction);
        $result['base_damage'] = $baseDamage;
        $result['damage_per_activation'] = $damagePerActivation;
        $result['expected_damage_per_eligible_attack'] = $damagePerActivation * $chance;
        $result['explanation'] = sprintf(
            '%s × %.1f%%, limitado a %.1f%% do HP da criatura; chance %.1f%%.',
            $attribute === 'hp' ? 'HP do personagem' : 'Mana do personagem',
            $fraction * 100,
            $capFraction * 100,
            $chance * 100,
        );

        return $this->markCalculable($result);
    }

    private function effectSummary(CharmDefinition $charm, CharmTier $tier): string
    {
        $chance = $tier->activation_chance === null ? null : $tier->activation_chance * 100;
        $value = $tier->effect_value;
        $config = $tier->effect_config ?? [];

        return match ($charm->effect_type) {
            'elemental_percent' => sprintf('%.0f%% chance · %.0f%% do HP máximo como dano %s', $chance, $value * 100, ucfirst($charm->element)),
            'kill_aoe_percent' => sprintf('%.0f%% chance ao matar · %.0f%% do HP máximo em área (%s)', $chance, $value * 100, ucfirst($charm->element)),
            'avoid_attack' => sprintf('%.0f%% chance de esquivar de um ataque', $chance),
            'critical_chance_bonus' => sprintf('+%.0f%% de chance de crítico', $value * 100),
            'character_hp_capped_percent' => sprintf('%.0f%% chance · até %.0f%% do HP do personagem, limitado a %.0f%% do HP da criatura', $chance, $value * 100, ($config['monster_cap_fraction'] ?? 0.08) * 100),
            'character_mana_capped_percent' => sprintf('%.0f%% chance · até %.1f%% da mana do personagem, limitado a %.0f%% do HP da criatura', $chance, $value * 100, ($config['monster_cap_fraction'] ?? 0.08) * 100),
            'reflect_incoming_damage' => sprintf('%.0f%% chance de refletir dano recebido; armadura aplica-se, resistência não', $chance),
            'critical_damage_bonus' => sprintf('+%.0f%% de dano crítico', $value * 100),
            'movement_speed' => sprintf('%.0f%% chance após ataque recebido · +%.0f%% velocidade por %d s', $chance, $value * 100, $config['duration_seconds']),
            'death_penalty_reduction' => sprintf('Reduz perda de skills/experiência em %.0f%%', $value * 100),
            'condition_immunity' => sprintf('%.0f%% chance após ataque recebido · imunidade por %d s', $chance, $config['duration_seconds']),
            'paralyze_creature' => sprintf('%.0f%% chance · paralisa por %d s', $chance, $config['duration_seconds']),
            'creature_products_bonus' => sprintf('+%.0f%% produtos de criaturas', $value * 100),
            'skinning_chance_bonus' => sprintf('+%.0f%% chances de Skinning/Dusting', $value * 100),
            'prevent_creature_escape' => sprintf('%.0f%% chance · impede fuga por %d s', $chance, $config['duration_seconds']),
            'life_leech_bonus' => sprintf('+%.1f%% Life Leech', $value * 100),
            'mana_drain_inversion' => sprintf('%.0f%% chance de inverter Mana Drain', $chance),
            'mana_leech_bonus' => sprintf('+%.1f%% Mana Leech', $value * 100),
            default => 'Efeito de referência cadastrado',
        };
    }

    private function markCalculable(array $result): array
    {
        $result['calculable'] = true;
        $result['damage_metric'] = 'expected_damage_per_eligible_attack';

        return $result;
    }
}
