<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\EventListener;

use MoveElevator\Typo3Toolbox\EventListener\ContentMinifierEventListener;
use MoveElevator\Typo3Toolbox\Minifier\HtmlMinifier;
use MoveElevator\Typo3Toolbox\Minifier\JavaScriptMinifier;
use MoveElevator\Typo3Toolbox\Minifier\ContentMask;
use MoveElevator\Typo3Toolbox\Minifier\StyleSheetMinifier;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContentMinifierEventListenerTest extends UnitTestCase
{
    #[Test]
    public function minifiedContentIsWrittenBackToTheEvent(): void
    {
        $event = new AfterCacheableContentIsGeneratedEvent(
            self::createStub(ServerRequestInterface::class),
            "<div>\n\t<p>\n\t\tHello\n\t</p>\n</div>",
            'cache-identifier',
            true
        );

        $listener = new ContentMinifierEventListener(new HtmlMinifier(
            new JavaScriptMinifier(),
            new StyleSheetMinifier(),
            new ContentMask()
        ));

        $listener($event);

        self::assertSame('<div><p>Hello</p></div>', $event->getContent());
    }
}
