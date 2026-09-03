<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\EventListener;

use MoveElevator\Typo3Toolbox\Minifier\HtmlMinifier;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

#[AsEventListener(identifier: 'moveElevator/contentMinifier')]
final readonly class ContentMinifierEventListener
{
    public function __construct(private HtmlMinifier $htmlMinifier)
    {
    }

    public function __invoke(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $event->setContent($this->htmlMinifier->minify($event->getContent()));
    }
}
