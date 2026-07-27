<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Widget\EndOfLife;

use MoveElevator\Typo3Toolbox\Widget\EndOfLife\LifecyclePhase;
use MoveElevator\Typo3Toolbox\Widget\EndOfLife\LifecyclePhaseType;
use MoveElevator\Typo3Toolbox\Widget\EndOfLife\TimeWindow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LifecyclePhaseTest extends UnitTestCase
{
    /**
     * A 100-day window, so day offsets map directly to percentages.
     */
    private function window(): TimeWindow
    {
        $start = new \DateTimeImmutable('2024-01-01T00:00:00+00:00');

        return new TimeWindow($start, $start->modify('+100 days'));
    }

    private function day(int $offset): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))->modify(sprintf('%+d days', $offset));
    }

    #[Test]
    #[DataProvider('widthProvider')]
    public function widthPercentOfClipsToWindow(?int $startDay, ?int $endDay, float $expected): void
    {
        $phase = new LifecyclePhase(
            LifecyclePhaseType::Security,
            $startDay === null ? null : $this->day($startDay),
            $endDay === null ? null : $this->day($endDay),
        );

        self::assertEqualsWithDelta($expected, $phase->widthPercentOf($this->window()), 0.0001);
    }

    /**
     * @return array<string, array{0: ?int, 1: ?int, 2: float}>
     */
    public static function widthProvider(): array
    {
        return [
            'fully inside the window' => [20, 40, 20.0],
            'clipped at the left edge' => [-10, 40, 40.0],
            'clipped at the right edge' => [80, 120, 20.0],
            'spanning the whole window' => [-10, 120, 100.0],
            'entirely before the window' => [-30, -10, 0.0],
            'entirely after the window' => [110, 130, 0.0],
            'open start clips to window start' => [null, 30, 30.0],
            'open end clips to window end' => [70, null, 30.0],
            'open on both ends fills the window' => [null, null, 100.0],
            'zero-length phase' => [50, 50, 0.0],
            'inverted range' => [60, 40, 0.0],
        ];
    }

    #[Test]
    public function phaseEndingExactlyAtWindowStartHasNoWidth(): void
    {
        $phase = new LifecyclePhase(LifecyclePhaseType::FullSupport, $this->day(-20), $this->day(0));

        self::assertSame(0.0, $phase->widthPercentOf($this->window()));
    }
}
