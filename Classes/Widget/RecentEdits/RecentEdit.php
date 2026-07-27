<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\RecentEdits;

/**
 * A single record the current backend user edited recently, prepared for display.
 */
final readonly class RecentEdit
{
    public function __construct(
        public string $table,
        public int $uid,
        public string $title,
        public string $tableLabel,
        public string $iconIdentifier,
        public string $editUrl,
        public ?int $pid,
        public string $pageTitle,
        public int $timestamp,
        public string $relativeAge,
    ) {
    }
}
