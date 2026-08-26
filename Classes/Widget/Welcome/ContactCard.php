<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

/**
 * Names a contact person for the project, with click-to-contact channels.
 *
 * {@see $image} is turned into a concrete {@see $imageUri} by the
 * {@see CardLinkResolver}; until then it is null.
 */
final readonly class ContactCard implements CardInterface
{
    /**
     * @param list<Channel> $channels
     */
    public function __construct(
        public string $name,
        public ?string $role,
        public ?string $image,
        public array $channels,
        private ?string $title,
        public ?string $imageUri = null,
    ) {
    }

    public function getPartial(): string
    {
        return 'Contact';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function withImageUri(string $imageUri): self
    {
        return new self($this->name, $this->role, $this->image, $this->channels, $this->title, $imageUri);
    }
}
