<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

/**
 * A single entry of a Welcome links card, pointing either at an external URL or
 * at a backend module route.
 *
 * Module routes are turned into a concrete {@see $href} by the
 * {@see CardLinkResolver}; until then it is null.
 */
final readonly class CardLink
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public string $label,
        public ?string $url,
        public ?string $module,
        public array $parameters,
        public ?string $iconIdentifier,
        public ?string $href = null,
    ) {
    }

    public function withHref(string $href): self
    {
        return new self(
            $this->label,
            $this->url,
            $this->module,
            $this->parameters,
            $this->iconIdentifier,
            $href,
        );
    }

    public function isExternal(): bool
    {
        return $this->url !== null;
    }
}
