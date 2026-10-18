<?php

namespace App\Services;

class TibiaCalculator
{
    public static function calculateCharm(array $selected, int $available): array
    {
        $required = 0;

        foreach ($selected as $item) {
            $required += max(0, (int) ($item['cost'] ?? 0));
        }

        $difference = $available - $required;

        return [
            'required' => (int) $required,
            'difference' => (int) $difference,
            'missing' => max(0, -(int) $difference),
            'remaining' => max(0, (int) $difference),
            'is_enough' => $difference >= 0,
        ];
    }

    public static function calculateImbuement(array $materials): array
    {
        $breakdown = [];
        $total = 0;

        foreach ($materials as $material) {
            $quantity = max(0, (int) ($material['quantity'] ?? 0));
            $unitPrice = max(0, (int) ($material['unit_price'] ?? 0));
            $subtotal = $quantity * $unitPrice;

            $breakdown[] = [
                'name' => (string) ($material['name'] ?? 'Material'),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => (int) $subtotal,
            ];

            $total += $subtotal;
        }

        return [
            'total' => (int) $total,
            'breakdown' => $breakdown,
        ];
    }
}
