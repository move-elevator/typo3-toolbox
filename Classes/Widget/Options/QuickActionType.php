<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

/**
 * The mutually exclusive kinds of a Quick Action.
 */
enum QuickActionType: string
{
    case Url = 'url';
    case Module = 'module';
    case Record = 'record';
}
