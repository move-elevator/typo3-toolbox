<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\EventListener;

use MoveElevator\Typo3Toolbox\EventListener\ContentMinifierEventListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContentMinifierEventListenerTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function contents(): \Generator
    {
        yield 'dangling closing bracket is pulled to the last attribute' => [
            "<div\n\tclass=\"panel\"\n\trole=\"tabpanel\"\n>content</div>",
            '<div class="panel" role="tabpanel">content</div>',
        ];

        yield 'dangling closing bracket of a self-closing tag is normalized' => [
            "<img\n\tsrc=\"logo.svg\"\n\talt=\"\"\n/>",
            '<img src="logo.svg" alt="">',
        ];

        yield 'redundant self-closing marker is removed from void elements' => [
            '<img src="logo.svg" alt="" /><br /><input type="text" />',
            '<img src="logo.svg" alt=""><br><input type="text">',
        ];

        yield 'closing bracket inside an attribute value is left untouched' => [
            '<img title="a > b" />',
            '<img title="a > b"/>',
        ];

        yield 'self-closing marker is kept for svg elements' => [
            '<svg viewBox="0 0 24 24"><path d="M0 0h24" /><path d="M4 4h8" /></svg>',
            '<svg viewBox="0 0 24 24"><path d="M0 0h24"/><path d="M4 4h8"/></svg>',
        ];

        yield 'self-closing marker is kept for svg elements with a dangling bracket' => [
            "<svg viewBox=\"0 0 24 24\">\n\t<path\n\t\td=\"M0 0h24\"\n\t/>\n</svg>",
            '<svg viewBox="0 0 24 24"><path d="M0 0h24"/></svg>',
        ];

        yield 'whitespace between tags is removed except before inline tags' => [
            "<div>\n<p>Hello</p>\n<span class=\"a\">World</span>\n</div>",
            '<div><p>Hello</p> <span class="a">World</span></div>',
        ];

        yield 'whitespace after tag start and before tag close is removed' => [
            "<p>\n\tText\n</p>",
            '<p>Text</p>',
        ];

        yield 'default type attributes are removed' => [
            '<script type="text/javascript" src="a.js"></script><style type="text/css">a{}</style>',
            '<script src="a.js"></script><style>a{}</style>',
        ];

        yield 'whitespace in class attributes is collapsed' => [
            "<div class=\"  a   b\n\tc \">x</div>",
            '<div class="a b c">x</div>',
        ];

        yield 'json-ld schema is minified' => [
            "<script type=\"application/ld+json\">\n{\n\t\"@context\": \"https://schema.org\"\n}\n</script>",
            '<script type="application/ld+json">{"@context":"https://schema.org"}</script>',
        ];

        yield 'invalid json-ld schema is dropped' => [
            '<script type="application/ld+json">{invalid}</script>',
            '',
        ];

        yield 'ckeditor list item attributes are removed' => [
            '<li data-list-item-id="e123">Item</li>',
            '<li>Item</li>',
        ];

        yield 'inline script line comment is not swallowed by newline collapsing' => [
            "<script>\n\t(function() {\n\t\t// some comment\n\t\tdoSomething();\n\t})();\n</script>",
            "<script>\n\t(function() {\n\t\t// some comment\n\t\tdoSomething();\n\t})();\n</script>",
        ];

        yield 'multiline style content is preserved while surrounding whitespace is still collapsed' => [
            "<div>\n\t<style>\n\t\t.a {\n\t\t\tcolor: red;\n\t\t}\n\t</style>\n</div>",
            "<div><style>\n\t\t.a {\n\t\t\tcolor: red;\n\t\t}\n\t</style></div>",
        ];

        yield 'whitespace around a script tag is still collapsed while its content is preserved' => [
            "<div>\n\t<script>\n\t\t// keep\n\t\tdoSomething();\n\t</script>\n</div>",
            "<div><script>\n\t\t// keep\n\t\tdoSomething();\n\t</script></div>",
        ];
    }

    #[Test]
    #[DataProvider('contents')]
    public function contentIsMinified(string $content, string $expected): void
    {
        $event = new AfterCacheableContentIsGeneratedEvent(
            self::createStub(ServerRequestInterface::class),
            $content,
            'cache-identifier',
            true
        );

        new ContentMinifierEventListener()($event);

        self::assertSame($expected, $event->getContent());
    }
}
