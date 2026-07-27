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
}
