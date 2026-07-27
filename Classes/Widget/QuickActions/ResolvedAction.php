<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\QuickActions;

/**
 * A Quick Action resolved to a concrete, ready-to-render link.
 */
final readonly class ResolvedAction
{
    public function __construct(
        public string $label,
        public string $iconIdentifier,
        public string $url,
        public bool $external,
    ) {
    }
}
