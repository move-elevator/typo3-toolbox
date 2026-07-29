<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

/**
 * Logo and claim shown in the Welcome widget footer.
 *
 * A null {@see $url} renders the claim as plain text rather than a dead link.
 */
final readonly class Branding
{
    public function __construct(
        public bool $enabled,
        public string $logo,
        public string $claim,
        public ?string $url,
    ) {
    }
}
