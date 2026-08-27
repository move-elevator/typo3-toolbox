<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

/**
 * Thrown when a widget is configured with invalid options.
 *
 * The message always carries the config path of the offending value
 * (e.g. `actions.2: an action requires exactly one of "url", "module" or "record"`),
 * so a misconfiguration points straight at the responsible option.
 */
final class InvalidWidgetOptionsException extends \RuntimeException
{
}
