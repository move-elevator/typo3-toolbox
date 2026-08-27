<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

/**
 * The contact channels a Welcome contact card can offer.
 */
enum ChannelType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Mobile = 'mobile';

    public function uriScheme(): string
    {
        return $this === self::Email ? 'mailto:' : 'tel:';
    }

    public function getIconIdentifier(): string
    {
        return match ($this) {
            self::Email => 'actions-envelope',
            self::Phone => 'actions-phone',
            self::Mobile => 'actions-device-mobile',
        };
    }
}
