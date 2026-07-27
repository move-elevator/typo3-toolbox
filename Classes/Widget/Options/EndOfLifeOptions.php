<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

use MoveElevator\Typo3Toolbox\Widget\EndOfLife\TimeWindow;

/**
 * Typed, validated options for the End-of-Life widget.
 */
final readonly class EndOfLifeOptions
{
    /**
     * @param list<ComponentRequest> $components explicitly configured components (excluding auto-detected TYPO3/PHP)
     */
    public function __construct(
        public array $components,
        public TimeWindow $timeWindow,
        public int $warningThresholdDays,
    ) {
    }
}
