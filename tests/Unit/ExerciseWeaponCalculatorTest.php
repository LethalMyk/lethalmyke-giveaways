<?php

namespace Tests\Unit;

use App\Services\ExerciseWeaponCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExerciseWeaponCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_a_knight_melee_skill_level(): void
    {
        $result = ExerciseWeaponCalculator::calculate([
            'vocation' => 'knight',
            'skill' => 'melee',
            'current_skill' => 10,
            'target_skill' => 11,
            'remaining_percent' => 100,
        ]);

        $this->assertSame(50, $result['required_points']);
        $this->assertSame(7, $result['charges_required']);
        $this->assertSame(14, $result['duration_seconds']);
        $this->assertSame(1, $result['weapons']['regular']['count']);
    }

    #[Test]
    public function it_combines_dummy_event_and_loyalty_bonuses(): void
    {
        $result = ExerciseWeaponCalculator::calculate([
            'vocation' => 'knight',
            'skill' => 'melee',
            'current_skill' => 10,
            'target_skill' => 11,
            'remaining_percent' => 100,
            'loyalty_percent' => 50,
            'dummy' => true,
            'double_event' => true,
        ]);

        $this->assertEqualsWithDelta(3.3, $result['training_multiplier'], 0.0001);
        $this->assertSame(3, $result['charges_required']);
    }

    #[Test]
    public function it_returns_zero_when_the_target_is_the_current_skill(): void
    {
        $result = ExerciseWeaponCalculator::calculate([
            'vocation' => 'knight',
            'skill' => 'melee',
            'current_skill' => 80,
            'target_skill' => 80,
        ]);

        $this->assertSame(0, $result['required_points']);
        $this->assertSame(0, $result['charges_required']);
        $this->assertSame(0, $result['gold_estimate']);
    }

    #[Test]
    public function it_rejects_skill_ranges_that_overflow_the_supported_number_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExerciseWeaponCalculator::calculate([
            'vocation' => 'knight',
            'skill' => 'magic',
            'current_skill' => 10,
            'target_skill' => 200,
        ]);
    }
}