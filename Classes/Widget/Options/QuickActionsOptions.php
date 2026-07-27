<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

/**
 * Typed, validated options for the Quick Actions widget.
 */
final readonly class QuickActionsOptions
{
    /**
     * @param list<QuickAction> $actions
     */
    public function __construct(
        public array $actions,
    ) {
    }
}
