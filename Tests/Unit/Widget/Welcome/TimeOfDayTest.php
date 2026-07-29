<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Widget\Welcome;

use MoveElevator\Typo3Toolbox\Widget\Welcome\TimeOfDay;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TimeOfDayTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{int, TimeOfDay}>
     */
    public static function hours(): \Generator
    {
        yield 'midnight is night' => [0, TimeOfDay::Night];
        yield 'last night hour' => [4, TimeOfDay::Night];
        yield 'morning starts at five' => [5, TimeOfDay::Morning];
        yield 'last morning hour' => [10, TimeOfDay::Morning];
        yield 'day starts at eleven' => [11, TimeOfDay::Day];
        yield 'last day hour' => [17, TimeOfDay::Day];
        yield 'evening starts at eighteen' => [18, TimeOfDay::Evening];
        yield 'last evening hour' => [21, TimeOfDay::Evening];
        yield 'night starts at twentytwo' => [22, TimeOfDay::Night];
        yield 'last hour of the day is night' => [23, TimeOfDay::Night];
    }

    #[Test]
    #[DataProvider('hours')]
    public function fromHourMapsToTheExpectedTimeOfDay(int $hour, TimeOfDay $expected): void
    {
        self::assertSame($expected, TimeOfDay::fromHour($hour));
    }

    #[Test]
    public function fromDateTimeUsesTheHourOfTheGivenMoment(): void
    {
        self::assertSame(
            TimeOfDay::Evening,
            TimeOfDay::fromDateTime(new \DateTimeImmutable('2026-07-28T19:42:00+00:00')),
        );
    }

    #[Test]
    public function everyTimeOfDayHasItsOwnTranslationKey(): void
    {
        $keys = array_map(static fn (TimeOfDay $t): string => $t->translationKey(), TimeOfDay::cases());

        self::assertSame($keys, array_unique($keys));
        self::assertContains('widgets.welcome.greeting.morning', $keys);
    }
}
