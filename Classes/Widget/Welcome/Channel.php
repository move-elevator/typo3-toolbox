<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

/**
 * A single contact channel of a Welcome contact card.
 *
 * The href is built here rather than in project configuration, so a phone number
 * can be written the way it should be read on screen.
 */
final readonly class Channel
{
    public function __construct(
        public ChannelType $type,
        public string $value,
        public ?string $label = null,
    ) {
    }

    public function getHref(): string
    {
        $target = $this->type === ChannelType::Email
            ? $this->value
            // Whitespace and typographic separators are not valid in a tel: URI.
            : (string)preg_replace('/[^\d+]/', '', $this->value);

        return $this->type->uriScheme() . $target;
    }

    public function getLabel(): string
    {
        return $this->label ?? $this->value;
    }

    public function getIconIdentifier(): string
    {
        return $this->type->getIconIdentifier();
    }
}
