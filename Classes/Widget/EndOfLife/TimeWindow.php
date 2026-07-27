<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\EndOfLife;

/**
 * The shared time axis of the End-of-Life widget.
 *
 * All lifecycle bars are laid out against the same window, so their segments
 * and the "today" marker align vertically across components.
 */
final readonly class TimeWindow
{
    public function __construct(
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
    ) {
        if ($end <= $start) {
            throw new \InvalidArgumentException('TimeWindow end must be after start.', 1753600000);
        }
    }

    public function durationSeconds(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    /**
     * Position of a point on the axis as a percentage (0..100), clamped to the window.
     */
    public function offsetPercentOf(\DateTimeImmutable $point): float
    {
        $offset = $point->getTimestamp() - $this->start->getTimestamp();
        $percent = $offset / $this->durationSeconds() * 100;

        return max(0.0, min(100.0, $percent));
    }

    public function contains(\DateTimeImmutable $point): bool
    {
        return $point >= $this->start && $point <= $this->end;
    }
}
