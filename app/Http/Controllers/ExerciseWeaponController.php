<?php

namespace App\Http\Controllers;

use App\Services\ExerciseWeaponCalculator;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExerciseWeaponController extends Controller
{
    private const VOCATIONS = ['knight', 'paladin', 'sorcerer', 'druid', 'monk'];

    private const SKILLS = ['melee', 'fist', 'distance', 'shield', 'magic'];

    public function show(Request $request): View
    {
        $settings = [
            'vocation' => $this->queryChoice($request, 'vocation', self::VOCATIONS, 'knight'),
            'skill' => $this->queryChoice($request, 'skill', self::SKILLS, 'melee'),
            'current_skill' => $this->queryInteger($request, 'current', 122, 0, 200),
            'target_skill' => $this->queryInteger($request, 'target', 125, 1, 200),
            'remaining_percent' => $this->queryInteger($request, 'remaining', 50, 0, 100),
            'loyalty_percent' => $this->queryInteger($request, 'loyalty', 0, 0, 50),
            'dummy' => $request->has('dummy') ? $request->boolean('dummy') : true,
            'double_event' => $request->has('double-event') ? $request->boolean('double-event') : true,
        ];

        if ($settings['target_skill'] < $settings['current_skill']) {
            $settings['target_skill'] = $settings['current_skill'];
        }

        return view('tools.exercise-weapons-calculator', compact('settings'));
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vocation' => ['required', 'string', 'in:'.implode(',', self::VOCATIONS)],
            'skill' => ['required', 'string', 'in:'.implode(',', self::SKILLS)],
            'current_skill' => ['required', 'integer', 'min:0', 'max:200'],
            'target_skill' => ['required', 'integer', 'gte:current_skill', 'max:200'],
            'remaining_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'loyalty_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'dummy' => ['required', 'boolean'],
            'double_event' => ['required', 'boolean'],
            'price_lasting' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'price_durable' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'price_regular' => ['required', 'integer', 'min:0', 'max:1000000000000'],
        ]);

        if ($validated['skill'] !== 'magic' && $validated['current_skill'] < 10) {
            return response()->json([
                'message' => 'Skills de combate começam no nível 10.',
                'errors' => ['current_skill' => ['Skills de combate começam no nível 10.']],
            ], 422);
        }

        try {
            return response()->json(ExerciseWeaponCalculator::calculate($validated));
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['target_skill' => [$exception->getMessage()]],
            ], 422);
        }
    }

    private function queryChoice(Request $request, string $key, array $allowed, string $fallback): string
    {
        $value = $request->query($key, $fallback);

        return is_string($value) && in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function queryInteger(Request $request, string $key, int $fallback, int $min, int $max): int
    {
        $value = filter_var($request->query($key, $fallback), FILTER_VALIDATE_INT);

        return $value === false ? $fallback : max($min, min($max, $value));
    }
}