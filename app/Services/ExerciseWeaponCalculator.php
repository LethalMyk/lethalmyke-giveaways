<?php

namespace App\Services;

use InvalidArgumentException;

class ExerciseWeaponCalculator
{
    private const VOCATION_FACTORS = [
        'knight' => ['melee' => 1.1, 'fist' => 1.1, 'distance' => 1.4, 'shield' => 1.1, 'magic' => 3.0],
        'paladin' => ['melee' => 1.2, 'fist' => 1.2, 'distance' => 1.1, 'shield' => 1.1, 'magic' => 1.4],
        'sorcerer' => ['melee' => 2.0, 'fist' => 1.5, 'distance' => 2.0, 'shield' => 1.5, 'magic' => 1.1],
        'druid' => ['melee' => 1.8, 'fist' => 1.5, 'distance' => 1.8, 'shield' => 1.5, 'magic' => 1.1],
        'monk' => ['melee' => 1.4, 'fist' => 1.1, 'distance' => 1.5, 'shield' => 1.15, 'magic' => 1.25],
    ];

    private const SKILL_CONSTANTS = [
        'melee' => 50,
        'fist' => 50,
        'distance' => 30,
        'shield' => 100,
        'magic' => 1600,
    ];

    private const POINTS_PER_CHARGE = [
        'melee' => 7.2,
        'fist' => 7.2,
        'distance' => 4.32,
        'shield' => 14.4,
        'magic' => 600,
    ];

    private const WEAPON_CHARGES = [
        'lasting' => 14400,
        'durable' => 1800,
        'regular' => 500,
    ];

    private const DEFAULT_PRICES = [
        'lasting' => 10000000,
        'durable' => 1250000,
        'regular' => 347222,
    ];

    public static function calculate(array $input): array
    {
        $vocation = (string) ($input['vocation'] ?? 'knight');
        $skill = (string) ($input['skill'] ?? 'melee');
        $current = (int) ($input['current_skill'] ?? 10);
        $target = (int) ($input['target_skill'] ?? 11);

        if (!isset(self::VOCATION_FACTORS[$vocation][$skill])) {
            throw new InvalidArgumentException('Vocation ou skill inválida.');
        }

        if ($current > 200 || $target > 200) {
            throw new InvalidArgumentException('Use skills até 200 para manter a estimativa dentro do limite numérico.');
        }

        if ($target < $current) {
            throw new InvalidArgumentException('O skill desejado deve ser igual ou maior que o skill atual.');
        }

        $remainingPercent = max(0, min(100, (float) ($input['remaining_percent'] ?? 100)));
        $offset = $skill === 'magic' ? 0 : 10;
        $skillConstant = self::SKILL_CONSTANTS[$skill];
        $vocationFactor = self::VOCATION_FACTORS[$vocation][$skill];
        $requiredPoints = 0.0;

        for ($level = $current; $level < $target; $level++) {
            $levelProgress = $level === $current ? $remainingPercent / 100 : 1;
            $requiredPoints += $skillConstant * ($vocationFactor ** ($level - $offset)) * $levelProgress;
        }

        if (!is_finite($requiredPoints) || $requiredPoints > PHP_INT_MAX) {
            throw new InvalidArgumentException('Essa combinação de vocação e skill excede o limite da estimativa.');
        }

        $trainingMultiplier = ($input['dummy'] ?? false) ? 1.1 : 1;
        $trainingMultiplier *= ($input['double_event'] ?? false) ? 2 : 1;
        $loyaltyBonus = max(0, min(50, (float) ($input['loyalty_percent'] ?? 0)));
        $trainingMultiplier *= 1 + ($loyaltyBonus / 100);

        $chargeEstimate = ceil(
            $requiredPoints / (self::POINTS_PER_CHARGE[$skill] * $trainingMultiplier),
        );
        if (!is_finite($chargeEstimate) || $chargeEstimate > intdiv(PHP_INT_MAX, 2)) {
            throw new InvalidArgumentException('Essa estimativa excede o limite numérico suportado.');
        }
        $chargesRequired = (int) $chargeEstimate;

        $remainingCharges = $chargesRequired;
        $weapons = [];
        foreach (self::WEAPON_CHARGES as $type => $charges) {
            $count = $type === 'regular'
                ? (int) ceil($remainingCharges / $charges)
                : intdiv($remainingCharges, $charges);
            $remainingCharges -= $count * $charges;

            $price = max(0, (int) ($input['price_'.$type] ?? self::DEFAULT_PRICES[$type]));
            $cost = $count * $price;
            if (!is_int($cost)) {
                throw new InvalidArgumentException('O custo estimado excede o limite numérico suportado.');
            }

            $weapons[$type] = [
                'count' => $count,
                'charges_per_weapon' => $charges,
                'price' => $price,
            ];
        }

        $goldEstimate = array_sum(array_map(
            fn (array $weapon): int => $weapon['count'] * $weapon['price'],
            $weapons,
        ));

        return [
            'required_points' => (int) ceil($requiredPoints),
            'charges_required' => $chargesRequired,
            'duration_seconds' => $chargesRequired * 2,
            'training_multiplier' => $trainingMultiplier,
            'gold_estimate' => $goldEstimate,
            'weapons' => $weapons,
        ];
    }
}