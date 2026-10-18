<?php

namespace App\Services;

use App\Models\Monster;
use InvalidArgumentException;

class CharmOptimizationService
{
    public function __construct(private readonly CharmCalculationService $calculator) {}

    public function optimize(array $entries, array $selectedCharms, string $criterion, array $character = []): array
    {
        if (! in_array($criterion, ['individual', 'hunt'], true)) {
            throw new InvalidArgumentException('Critério de otimização inválido.');
        }

        $definitions = $this->calculator->loadSelected($selectedCharms);
        $rows = [];
        $majorByMonster = [];
        $minorByMonster = [];

        foreach ($entries as $index => $entry) {
            /** @var Monster $monster */
            $monster = $entry['monster'];
            $comparison = $this->calculator->compare($monster, $selectedCharms, $character, $definitions);
            $rows[$index] = [
                ...$comparison,
                'estimated_kills' => $entry['kills'],
                'weighted_expected_damage' => null,
            ];
            $majorByMonster[$index] = collect($comparison['alternatives'])
                ->filter(fn (array $alternative): bool => $alternative['charm_type'] === 'major' && $alternative['calculable'])
                ->values()
                ->all();
            $minorByMonster[$index] = collect($comparison['alternatives'])
                ->filter(fn (array $alternative): bool => $alternative['charm_type'] === 'minor' && $alternative['calculable'])
                ->values()
                ->all();
        }

        $accountType = $character['account_type'] ?? 'premium';
        $majorCapacity = (int) config("charms.account_major_capacity.{$accountType}", 0);
        $weights = fn (int $index): float => $criterion === 'hunt' ? (float) $entries[$index]['kills'] : 1.0;

        // Cada charm só pode ser equipado em uma criatura.
        $majorAssignment = $this->assign($majorByMonster, $weights, $majorCapacity);
        $minorAssignment = $this->assign($minorByMonster, $weights, PHP_INT_MAX);
        $huntPreset = [];

        foreach ($rows as $index => &$row) {
            $major = $majorAssignment[$index] ?? null;
            $minor = $minorAssignment[$index] ?? null;
            $combination = array_values(array_filter([$major, $minor]));
            $row['recommended'] = $combination[0] ?? null;
            $row['recommended_combination'] = $combination;
            $row['selected_charms'] = $combination;
            $row['weighted_expected_damage'] = collect($combination)
                ->sum('expected_damage_per_eligible_attack')
                * ($criterion === 'hunt' ? $row['estimated_kills'] : 1);

            foreach ($combination as $charm) {
                $huntPreset[$charm['charm_slug']] = [
                    'charm_slug' => $charm['charm_slug'],
                    'charm' => $charm['charm'],
                    'tier' => $charm['tier'],
                    'charm_type' => $charm['charm_type'],
                ];
            }
        }
        unset($row);

        if ($criterion === 'hunt') {
            usort($rows, static fn (array $a, array $b): int => [$b['estimated_kills'], $b['weighted_expected_damage'] ?? -1] <=> [$a['estimated_kills'], $a['weighted_expected_damage'] ?? -1]);
        }

        return [
            'criterion' => $criterion,
            'results' => $rows,
            'hunt_preset' => array_values($huntPreset),
            'account_type' => $accountType,
            'major_charm_capacity' => $majorCapacity,
            'total_expected_hunt_damage' => null,
            'limitation' => 'Dano total da hunt não calculado: faltam ataques elegíveis por criatura. Kills são usadas apenas como peso comparativo. Efeitos sem métrica de dano não são comparados nem selecionados automaticamente.',
        ];
    }

    /**
     * Atribui cada charm a no máximo uma criatura (e cada criatura a no máximo um charm do tipo),
     * maximizando o dano ponderado total, com no máximo $limit atribuições (fluxo de custo mínimo).
     *
     * @param  array<int, array<int, array>>  $alternativesByMonster
     * @return array<int, array>  alternativa escolhida por índice de criatura
     */
    private function assign(array $alternativesByMonster, callable $weight, int $limit): array
    {
        $monsters = array_keys($alternativesByMonster);
        $slugs = [];
        $best = [];
        foreach ($alternativesByMonster as $index => $alternatives) {
            foreach ($alternatives as $alternative) {
                $value = $alternative['expected_damage_per_eligible_attack'] * $weight($index);
                if ($value <= 0) {
                    continue;
                }
                $slug = $alternative['charm_slug'];
                $slugs[$slug] = true;
                if (! isset($best[$index][$slug]) || $value > $best[$index][$slug]['value']) {
                    $best[$index][$slug] = ['value' => $value, 'alternative' => $alternative];
                }
            }
        }

        $slugs = array_keys($slugs);
        sort($slugs);
        $monsterCount = count($monsters);
        $source = 0;
        $sink = 1 + $monsterCount + count($slugs);
        $edges = [];
        $graph = array_fill(0, $sink + 1, []);
        $addEdge = function (int $from, int $to, float $cost) use (&$edges, &$graph): void {
            $graph[$from][] = count($edges);
            $edges[] = ['to' => $to, 'cap' => 1, 'cost' => $cost];
            $graph[$to][] = count($edges);
            $edges[] = ['to' => $from, 'cap' => 0, 'cost' => -$cost];
        };

        foreach ($monsters as $position => $index) {
            $addEdge($source, 1 + $position, 0.0);
            foreach ($best[$index] ?? [] as $slug => $candidate) {
                $addEdge(1 + $position, 1 + $monsterCount + array_search($slug, $slugs, true), -$candidate['value']);
            }
        }
        foreach ($slugs as $position => $slug) {
            $addEdge(1 + $monsterCount + $position, $sink, 0.0);
        }

        for ($flow = 0; $flow < $limit; $flow++) {
            $distance = array_fill(0, $sink + 1, INF);
            $previous = array_fill(0, $sink + 1, null);
            $distance[$source] = 0.0;
            for ($pass = 0; $pass <= $sink; $pass++) {
                $changed = false;
                foreach ($graph as $node => $edgeIds) {
                    if ($distance[$node] === INF) {
                        continue;
                    }
                    foreach ($edgeIds as $edgeId) {
                        $edge = $edges[$edgeId];
                        if ($edge['cap'] > 0 && $distance[$node] + $edge['cost'] < $distance[$edge['to']] - 1e-12) {
                            $distance[$edge['to']] = $distance[$node] + $edge['cost'];
                            $previous[$edge['to']] = $edgeId;
                            $changed = true;
                        }
                    }
                }
                if (! $changed) {
                    break;
                }
            }

            if ($distance[$sink] === INF || $distance[$sink] >= 0) {
                break;
            }

            for ($node = $sink; $node !== $source;) {
                $edgeId = $previous[$node];
                $edges[$edgeId]['cap']--;
                $edges[$edgeId ^ 1]['cap']++;
                $node = $edges[$edgeId ^ 1]['to'];
            }
        }

        $result = [];
        foreach ($monsters as $position => $index) {
            foreach ($graph[1 + $position] as $edgeId) {
                $edge = $edges[$edgeId];
                $isForward = ($edgeId & 1) === 0;
                if ($isForward && $edge['cap'] === 0 && $edge['to'] > $monsterCount) {
                    $slug = $slugs[$edge['to'] - 1 - $monsterCount];
                    $result[$index] = $best[$index][$slug]['alternative'];
                }
            }
        }

        return $result;
    }
}
