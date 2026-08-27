<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Widget\Options;

use MoveElevator\Typo3Toolbox\Widget\Options\InvalidWidgetOptionsException;
use MoveElevator\Typo3Toolbox\Widget\Options\WidgetOptionsFactory;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class WidgetOptionsFactoryTest extends UnitTestCase
{
    private WidgetOptionsFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new WidgetOptionsFactory();
    }

    #[Test]
    public function endOfLifeComponentRequiresProduct(): void
    {
        $this->expectException(InvalidWidgetOptionsException::class);
        $this->expectExceptionMessage('components.0.product: is required and must not be empty');

        $this->subject->createEndOfLifeOptions(
            ['components' => [['version' => '22']]],
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
    }

    #[Test]
    public function endOfLifeBuildsRelativeTimeWindowAroundNow(): void
    {
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $options = $this->subject->createEndOfLifeOptions([], $now);

        self::assertSame('2025-01-01', $options->timeWindow->start->format('Y-m-d'));
        self::assertSame('2030-01-01', $options->timeWindow->end->format('Y-m-d'));
        self::assertSame(180, $options->warningThresholdDays);
    }

    #[Test]
    public function decimalWarningThresholdStringIsRejectedInsteadOfTruncated(): void
    {
        $this->expectException(InvalidWidgetOptionsException::class);
        $this->expectExceptionMessage('warningThresholdDays: must be an integer');

        $this->subject->createEndOfLifeOptions(
            ['warningThresholdDays' => '1.5'],
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
    }

    #[Test]
    public function nonBooleanEltsContractIsRejectedWithItsPath(): void
    {
        $this->expectException(InvalidWidgetOptionsException::class);
        $this->expectExceptionMessage('components.0.eltsContract: must be a boolean');

        $this->subject->createEndOfLifeOptions(
            ['components' => [['product' => 'php', 'version' => '8.4', 'eltsContract' => 'maybe']]],
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
    }

    #[Test]
    public function normalizedInvalidCalendarDateIsRejected(): void
    {
        $this->expectException(InvalidWidgetOptionsException::class);
        $this->expectExceptionMessage('timeWindow.from: "2026-02-30" is not a valid date');

        $this->subject->createEndOfLifeOptions(
            ['timeWindow' => ['from' => '2026-02-30']],
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
    }
}
