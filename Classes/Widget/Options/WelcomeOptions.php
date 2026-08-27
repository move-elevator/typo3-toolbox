<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

use MoveElevator\Typo3Toolbox\Widget\Welcome\CardInterface;

/**
 * Validated options of the Welcome widget.
 */
final readonly class WelcomeOptions
{
    /**
     * @param list<CardInterface> $cards
     */
    public function __construct(
        public ?string $emoji,
        public ?string $intro,
        public Branding $branding,
        public array $cards,
    ) {
    }
}
