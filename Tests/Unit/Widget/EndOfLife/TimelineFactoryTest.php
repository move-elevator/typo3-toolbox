<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Widget\EndOfLife;

use MoveElevator\Typo3Toolbox\Widget\EndOfLife\ComponentLifecycle;
use MoveElevator\Typo3Toolbox\Widget\EndOfLife\LifecyclePhase;
use MoveElevator\Typo3Toolbox\Widget\EndOfLife\LifecyclePhaseType;
use MoveElevator\Typo3Toolbox\Widget\EndOfLife\TimelineFactory;
use MoveElevator\Typo3Toolbox\Widget\EndOfLife\TimeWindow;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TimelineFactoryTest extends UnitTestCase
{
    private const string NOW = '2026-07-01T00:00:00+00:00';

    #[Test]
    public function segmentsCarryFormattedDateLabelsForTheHover(): void
    {
        $segments = $this->buildSegments([
            new LifecyclePhase(
                LifecyclePhaseType::FullSupport,
                new \DateTimeImmutable('2025-04-08T00:00:00+00:00'),
                new \DateTimeImmutable('2027-04-30T00:00:00+00:00'),
            ),
        ]);

        self::assertSame('2025-04-08', $segments[0]['startLabel']);
        self::assertSame('2027-04-30', $segments[0]['endLabel']);
    }

    #[Test]
    public function openEndedPhaseHasNoEndLabel(): void
    {
        $segments = $this->buildSegments([
            new LifecyclePhase(
                LifecyclePhaseType::Security,
                new \DateTimeImmutable('2025-04-08T00:00:00+00:00'),
                null,
            ),
        ]);

        self::assertSame('2025-04-08', $segments[0]['startLabel']);
        self::assertNull($segments[0]['endLabel']);
    }

    /**
     * The v1 API encodes "boundary reached at an unknown date" as the epoch
     * marker — that must not be rendered as a real 1970 date.
     */
    #[Test]
    public function epochMarkerIsReportedAsUnknownDate(): void
    {
        $segments = $this->buildSegments([
            new LifecyclePhase(
                LifecyclePhaseType::FullSupport,
                new \DateTimeImmutable('@0'),
                new \DateTimeImmutable('2026-10-01T00:00:00+00:00'),
            ),
        ]);

        self::assertNull($segments[0]['startLabel']);
        self::assertSame('2026-10-01', $segments[0]['endLabel']);
    }

    /**
     * Once support has run out the remaining axis must be an explicit segment —
     * otherwise the bare track shows through and reads like another phase.
     */
    #[Test]
    public function remainingAxisAfterTheLastPhaseBecomesAnUnsupportedSegment(): void
    {
        $now = new \DateTimeImmutable(self::NOW);
        $segments = $this->buildSegments([
            new LifecyclePhase(
                LifecyclePhaseType::FullSupport,
                $now->modify('-2 years'),
                $now->modify('+2 years'),
            ),
        ]);

        self::assertCount(2, $segments);
        self::assertSame('unsupported', $segments[1]['type']);
        self::assertSame('unsupported', $segments[1]['cssModifier']);
        // Half the eight-year window is left after the phase ends.
        self::assertEqualsWithDelta(25.0, $segments[1]['widthPercent'], 0.5);
    }

    #[Test]
    public function openEndedPhaseLeavesNoUnsupportedSegment(): void
    {
        $now = new \DateTimeImmutable(self::NOW);
        $segments = $this->buildSegments([
            new LifecyclePhase(LifecyclePhaseType::FullSupport, $now->modify('-2 years'), null),
        ]);

        self::assertCount(1, $segments);
        self::assertSame('fullSupport', $segments[0]['type']);
    }

    #[Test]
    public function segmentsCarryTheCssModifierOfTheirPhase(): void
    {
        $segments = $this->buildSegments([
            new LifecyclePhase(
                LifecyclePhaseType::FullSupport,
                new \DateTimeImmutable('2025-04-08T00:00:00+00:00'),
                new \DateTimeImmutable('2026-10-01T00:00:00+00:00'),
            ),
            new LifecyclePhase(
                LifecyclePhaseType::Elts,
                new \DateTimeImmutable('2026-10-01T00:00:00+00:00'),
                new \DateTimeImmutable('2027-10-01T00:00:00+00:00'),
            ),
        ]);

        self::assertSame('full-support', $segments[0]['cssModifier']);
        self::assertSame('elts', $segments[1]['cssModifier']);
    }

    /**
     * @param list<LifecyclePhase> $phases
     * @return list<array<string, mixed>>
     */
    private function buildSegments(array $phases): array
    {
        $now = new \DateTimeImmutable(self::NOW);
        $window = new TimeWindow($now->modify('-4 years'), $now->modify('+4 years'));

        $timeline = new TimelineFactory()->build(
            [new ComponentLifecycle('typo3', '14.3', 'TYPO3', $phases, false, null, null)],
            $window,
            $now,
            180,
        );

        return $timeline['bars'][0]['segments'];
    }
}
