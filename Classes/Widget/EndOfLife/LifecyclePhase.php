<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\EndOfLife;

/**
 * A single phase of a component's lifecycle on the shared time axis.
 *
 * A null {@see $start} means the phase reaches back before the visible window
 * (open start); a null {@see $end} means it is still ongoing (open end).
 */
final readonly class LifecyclePhase
{
    public function __construct(
        public LifecyclePhaseType $type,
        public ?\DateTimeImmutable $start,
        public ?\DateTimeImmutable $end,
    ) {
    }

    /**
     * Width of this phase as a percentage of the given window, clipped to it.
     *
     * Phases lying entirely outside the window contribute 0; partially visible
     * phases are clipped to the window bounds.
     */
    public function widthPercentOf(TimeWindow $window): float
    {
        $start = $this->start ?? $window->start;
        $end = $this->end ?? $window->end;

        $clippedStart = max($start->getTimestamp(), $window->start->getTimestamp());
        $clippedEnd = min($end->getTimestamp(), $window->end->getTimestamp());

        if ($clippedEnd <= $clippedStart) {
            return 0.0;
        }

        return ($clippedEnd - $clippedStart) / $window->durationSeconds() * 100;
    }
}
