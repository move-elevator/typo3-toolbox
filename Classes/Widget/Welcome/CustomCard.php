<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

/**
 * Escape hatch for markup that no typed card covers.
 *
 * The HTML is rendered unescaped, so it is only ever as trustworthy as the
 * deployed configuration it comes from — never put user input here.
 */
final readonly class CustomCard implements CardInterface
{
    public function __construct(
        public string $html,
        private ?string $title,
    ) {
    }

    public function getPartial(): string
    {
        return 'Custom';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
