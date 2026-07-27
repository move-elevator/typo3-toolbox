<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

/**
 * Typed, validated options for the Recent Edits widget.
 */
final readonly class RecentEditsOptions
{
    /**
     * @param list<string> $allowedTables  empty = all tables allowed
     * @param list<string> $excludedTables tables never shown (technical tables by default)
     */
    public function __construct(
        public int $limit,
        public array $allowedTables,
        public array $excludedTables,
    ) {
    }
}
