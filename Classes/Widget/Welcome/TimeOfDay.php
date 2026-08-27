<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

/**
 * The part of the day a greeting is phrased for.
 */
enum TimeOfDay: string
{
    case Morning = 'morning';
    case Day = 'day';
    case Evening = 'evening';
    case Night = 'night';

    public static function fromDateTime(\DateTimeImmutable $moment): self
    {
        return self::fromHour((int)$moment->format('G'));
    }

    public static function fromHour(int $hour): self
    {
        return match (true) {
            $hour >= 5 && $hour < 11 => self::Morning,
            $hour >= 11 && $hour < 18 => self::Day,
            $hour >= 18 && $hour < 22 => self::Evening,
            default => self::Night,
        };
    }

    public function translationKey(): string
    {
        return 'widgets.welcome.greeting.' . $this->value;
    }
}
