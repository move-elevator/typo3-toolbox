<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

/**
 * A single component whose lifecycle should be shown in the End-of-Life widget.
 *
 * `product` is an endoflife.date product identifier (e.g. `typo3`, `php`, `nodejs`).
 * TYPO3 and PHP are detected automatically; further components are configured
 * explicitly. Listing `typo3` or `php` explicitly overrides the auto-detected
 * entry (e.g. to flag an ELTS contract).
 */
final readonly class ComponentRequest
{
    public function __construct(
        public string $product,
        public string $version,
        public bool $eltsContract,
        public ?string $label,
    ) {
    }

    public function label(): string
    {
        return $this->label ?? ucfirst($this->product);
    }
}
