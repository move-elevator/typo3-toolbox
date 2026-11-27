<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\ViewHelpers\Asset;

use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Leaves attribute values unencoded, because the AssetRenderer encodes them when rendering.
 */
final class UnescapedTagBuilder extends TagBuilder
{
    /**
     * Replaces the inherited PHPDoc type, whose array and Traversable lack value types.
     *
     * @param mixed $attributeValue
     */
    #[\Override]
    public function addAttribute(string $attributeName, mixed $attributeValue, bool $escapeSpecialCharacters = false): void
    {
        parent::addAttribute($attributeName, $attributeValue, false);
    }
}
