<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Widget\EndOfLife;

use MoveElevator\Typo3Toolbox\Widget\EndOfLife\TimeWindow;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TimeWindowTest extends UnitTestCase
{
    private function window(): TimeWindow
    {
        $start = new \DateTimeImmutable('2024-01-01T00:00:00+00:00');

        return new TimeWindow($start, $start->modify('+100 days'));
    }

    #[Test]
    public function offsetPercentOfReturnsPositionWithinWindow(): void
    {
        $point = (new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))->modify('+25 days');

        self::assertEqualsWithDelta(25.0, $this->window()->offsetPercentOf($point), 0.0001);
    }

    #[Test]
    public function offsetPercentOfClampsPointsOutsideTheWindow(): void
    {
        $window = $this->window();
        $before = (new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))->modify('-40 days');
        $after = (new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))->modify('+140 days');

        self::assertSame(0.0, $window->offsetPercentOf($before));
        self::assertSame(100.0, $window->offsetPercentOf($after));
    }

    #[Test]
    public function containsDetectsPointsInsideAndOutside(): void
    {
        $window = $this->window();

        self::assertTrue($window->contains((new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))->modify('+50 days')));
        self::assertFalse($window->contains((new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))->modify('+200 days')));
    }

    #[Test]
    public function constructorRejectsNonPositiveDuration(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $start = new \DateTimeImmutable('2024-01-01T00:00:00+00:00');
        new TimeWindow($start, $start);
    }
}
