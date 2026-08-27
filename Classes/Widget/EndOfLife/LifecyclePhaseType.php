<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\EndOfLife;

/**
 * The ordered lifecycle phases of a component.
 *
 * Modeled as an ordered list (full support → security → ELTS) so products
 * without extended support (e.g. PHP) simply omit the trailing phase, with
 * no special-casing needed anywhere.
 */
enum LifecyclePhaseType: string
{
    case FullSupport = 'fullSupport';
    case Security = 'security';
    case Elts = 'elts';

    /**
     * BEM modifier of the segment and legend swatch representing this phase.
     */
    public function cssModifier(): string
    {
        return match ($this) {
            self::FullSupport => 'full-support',
            self::Security => 'security',
            self::Elts => 'elts',
        };
    }
}
