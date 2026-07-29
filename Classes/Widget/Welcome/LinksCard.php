<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

/**
 * A collection of project links, each pointing at a URL or a backend module.
 */
final readonly class LinksCard implements CardInterface
{
    /**
     * @param list<CardLink> $links
     */
    public function __construct(
        public array $links,
        private ?string $title,
    ) {
    }

    public function getPartial(): string
    {
        return 'Links';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param list<CardLink> $links
     */
    public function withLinks(array $links): self
    {
        return new self($links, $this->title);
    }
}
