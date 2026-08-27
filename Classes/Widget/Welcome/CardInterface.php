<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

/**
 * One content block of the Welcome widget.
 *
 * Each card type brings its own Fluid partial, so adding a type means adding an
 * implementation plus a partial — no branching in the template.
 */
interface CardInterface
{
    /**
     * Name of the partial rendering this card, relative to `Widget/Welcome/`.
     */
    public function getPartial(): string;

    public function getTitle(): ?string;
}
