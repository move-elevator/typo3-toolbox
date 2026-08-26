<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Widget\EndOfLife;

use MoveElevator\Typo3Toolbox\Widget\EndOfLife\ComponentLifecycle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ComponentLifecycleTest extends UnitTestCase
{
    private function day(int $offset): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2024-01-01T00:00:00+00:00')->modify(sprintf('%+d days', $offset));
    }

    private function component(
        ?\DateTimeImmutable $securityEnd,
        ?\DateTimeImmutable $eltsEnd,
        bool $eltsContract = false,
        bool $endOfLifeReached = false,
    ): ComponentLifecycle {
        return new ComponentLifecycle(
            product: 'php',
            version: '8.4',
            label: 'PHP',
            phases: [],
            eltsContract: $eltsContract,
            securityEnd: $securityEnd,
            eltsEnd: $eltsEnd,
            endOfLifeReached: $endOfLifeReached,
        );
    }

    #[Test]
    #[DataProvider('inEltsBoundaryProvider')]
    public function isInEltsCoversTheInclusiveBoundary(int $nowOffset, bool $expected): void
    {
        $component = $this->component($this->day(0), $this->day(100));

        self::assertSame($expected, $component->isInElts($this->day($nowOffset)));
    }

    /**
     * @return array<string, array{0: int, 1: bool}>
     */
    public static function inEltsBoundaryProvider(): array
    {
        return [
            'before security end' => [-1, false],
            'exactly at security end' => [0, true],
            'inside the elts window' => [50, true],
            'exactly at elts end' => [100, true],
            'after elts end' => [101, false],
        ];
    }

    #[Test]
    #[DataProvider('missingBoundaryProvider')]
    public function isInEltsIsFalseWithoutBothBoundaries(?\DateTimeImmutable $securityEnd, ?\DateTimeImmutable $eltsEnd): void
    {
        $component = $this->component($securityEnd, $eltsEnd);

        self::assertFalse($component->isInElts($this->day(50)));
    }

    /**
     * @return array<string, array{0: ?\DateTimeImmutable, 1: ?\DateTimeImmutable}>
     */
    public static function missingBoundaryProvider(): array
    {
        $day = static fn (int $offset): \DateTimeImmutable => new \DateTimeImmutable('2024-01-01T00:00:00+00:00')->modify(sprintf('%+d days', $offset));

        return [
            'no security end' => [null, $day(100)],
            'no elts end' => [$day(0), null],
            'neither boundary' => [null, null],
        ];
    }

    #[Test]
    public function eltsRequiredAndEltsActiveDependOnTheContractFlag(): void
    {
        $now = $this->day(50);
        $withoutContract = $this->component($this->day(0), $this->day(100), eltsContract: false);
        $withContract = $this->component($this->day(0), $this->day(100), eltsContract: true);

        self::assertTrue($withoutContract->eltsRequired($now));
        self::assertFalse($withoutContract->eltsActive($now));
        self::assertFalse($withContract->eltsRequired($now));
        self::assertTrue($withContract->eltsActive($now));
    }

    #[Test]
    public function isEndOfLifePrefersTheEltsPhaseOverTheEndOfLifeReachedFlag(): void
    {
        $component = $this->component($this->day(0), $this->day(100), endOfLifeReached: true);

        self::assertFalse($component->isEndOfLife($this->day(50)));
        self::assertTrue($component->isEndOfLife($this->day(101)));
    }

    #[Test]
    public function isEndOfLifeIsTrueWhenTheFlagIsSetAndThereIsNoEltsPhase(): void
    {
        $component = $this->component(null, null, endOfLifeReached: true);

        self::assertTrue($component->isEndOfLife($this->day(0)));
    }

    #[Test]
    public function isEndOfLifeIsTrueOnceSecurityEndIsReachedWithoutAnEltsPhase(): void
    {
        $component = $this->component($this->day(0), null);

        self::assertFalse($component->isEndOfLife($this->day(-1)));
        self::assertTrue($component->isEndOfLife($this->day(0)));
    }

    #[Test]
    #[DataProvider('securityEndsSoonProvider')]
    public function securityEndsSoonRespectsTheWarningWindow(int $nowOffset, int $warningDays, bool $expected): void
    {
        $component = $this->component($this->day(30), null);

        self::assertSame($expected, $component->securityEndsSoon($this->day($nowOffset), $warningDays));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: bool}>
     */
    public static function securityEndsSoonProvider(): array
    {
        return [
            'far outside the warning window' => [0, 10, false],
            'exactly at the warning threshold' => [20, 10, true],
            'inside the warning window' => [25, 10, true],
            'already reached' => [30, 10, false],
            'past security end' => [31, 10, false],
        ];
    }

    #[Test]
    public function securityEndsSoonIsFalseWithoutASecurityEnd(): void
    {
        $component = $this->component(null, null);

        self::assertFalse($component->securityEndsSoon($this->day(0), 180));
    }
}
