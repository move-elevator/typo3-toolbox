<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

/**
 * A single configured Quick Action.
 *
 * Exactly one of {@see $url}, {@see $module} or {@see $record} is set,
 * matching {@see $type}. The remaining target fields are null.
 */
final readonly class QuickAction
{
    /**
     * @param array<string, string> $moduleParameters GET parameters for a module route
     * @param list<string>          $beGroups         backend group ids the action is limited to (empty = everyone)
     */
    public function __construct(
        public QuickActionType $type,
        public string $label,
        public ?string $iconIdentifier,
        public ?string $url,
        public ?string $module,
        public array $moduleParameters,
        public ?string $recordTable,
        public ?int $recordPid,
        public array $beGroups,
    ) {
    }
}
