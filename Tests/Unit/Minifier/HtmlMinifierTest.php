<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Minifier;

use MoveElevator\Typo3Toolbox\Minifier\HtmlMinifier;
use MoveElevator\Typo3Toolbox\Minifier\JavaScriptMinifier;
use MoveElevator\Typo3Toolbox\Minifier\ContentMask;
use MoveElevator\Typo3Toolbox\Minifier\StyleSheetMinifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class HtmlMinifierTest extends UnitTestCase
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
            '<img title="a > b">',
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

        yield 'line comments are removed from inline javascript' => [
            "<script>\n\tvar a = 1; // configure a\n\tvar b = 2;\n</script>",
            '<script>var a=1;var b=2;</script>',
        ];

        yield 'block comments are removed from inline javascript' => [
            "<script>\n/**\n * docblock\n */\nvar a = 1;\n</script>",
            '<script>var a=1;</script>',
        ];

        yield 'double slash inside a javascript string is kept' => [
            '<script>var u = "https://example.com/"; // drop</script>',
            '<script>var u="https://example.com/";</script>',
        ];

        yield 'double slash inside a regular expression literal is kept' => [
            '<script>var re = /\\/\\//; // drop</script>',
            '<script>var re=/\\/\\//;</script>',
        ];

        yield 'block comment opener inside a javascript string is kept' => [
            '<script>var s = "/* not a comment */";</script>',
            '<script>var s="/* not a comment */";</script>',
        ];

        yield 'template literal content is kept' => [
            '<script>var t = `a // b /* c */`;</script>',
            '<script>var t=`a // b /* c */`;</script>',
        ];

        yield 'division is not mistaken for a comment' => [
            '<script>var x = a / b / c; // drop</script>',
            '<script>var x=a/b/c;</script>',
        ];

        yield 'a regular expression after a keyword is kept' => [
            '<script>return /ab\\/c/.test(x); // drop</script>',
            '<script>return/ab\\/c/.test(x);</script>',
        ];

        yield 'removing a block comment does not join tokens' => [
            '<script>var x = a/**/+b;</script>',
            '<script>var x=a+b;</script>',
        ];

        yield 'comments are removed from inline styles' => [
            "<style>\n/* colors */\na { color: red; }\n</style>",
            '<style>a{color:red;}</style>',
        ];

        yield 'double slash inside a css url is kept' => [
            '<style>a{background:url(https://example.com/a.png)}/* drop */</style>',
            '<style>a{background:url(https://example.com/a.png)}</style>',
        ];

        yield 'comments in non-javascript script payloads are kept' => [
            '<script type="text/x-template">// keep me</script>',
            '<script type="text/x-template">// keep me</script>',
        ];

        yield 'comments in module scripts are removed' => [
            '<script type="module">import a from "./a.js"; // drop</script>',
            '<script type="module">import a from"./a.js";</script>',
        ];

        yield 'uppercase script tags are handled' => [
            "<SCRIPT>\nvar a = 1; // drop\n</SCRIPT>",
            '<SCRIPT>var a=1;</SCRIPT>',
        ];

        yield 'an unterminated block comment does not swallow the rest of the document' => [
            '<script>var x = 1; /* unterminated</script><p>after</p>',
            '<script>var x=1;</script><p>after</p>',
        ];

        yield 'json-ld containing an url is minified' => [
            "<script type=\"application/ld+json\">\n{\n\t\"@id\": \"https://example.com/#org\"\n}\n</script>",
            '<script type="application/ld+json">{"@id":"https://example.com/#org"}</script>',
        ];

        yield 'nested template literals are kept' => [
            '<script>var t = `${ `https://x` }`; // drop</script>',
            '<script>var t=`${`https://x`}`;</script>',
        ];

        yield 'division after an increment is not mistaken for a regular expression' => [
            "<script>var next = d++ / e; // drop\nvar z = 3;</script>",
            '<script>var next=d++/e;var z=3;</script>',
        ];

        yield 'protocol relative urls in javascript are kept' => [
            '<script>var cdn = "//cdn.example.com/a.js"; // drop</script>',
            '<script>var cdn="//cdn.example.com/a.js";</script>',
        ];

        yield 'comment markers inside css strings are kept' => [
            '<style>.a::after{content:"/* keep */"}/* drop */</style>',
            '<style>.a::after{content:"/* keep */"}</style>',
        ];

        yield 'whitespace inside javascript strings is kept' => [
            '<script>var s = "a    b"; // drop</script>',
            '<script>var s="a    b";</script>',
        ];

        yield 'linebreaks inside template literals are kept' => [
            "<script>var t = `line1\n    line2`; // drop</script>",
            "<script>var t=`line1\n    line2`;</script>",
        ];

        yield 'whitespace inside css strings is kept' => [
            '<style>.a::after{content:"a    b"}/* drop */</style>',
            '<style>.a::after{content:"a    b"}</style>',
        ];

        yield 'whitespace around javascript code is still collapsed' => [
            "<script>\nvar a = 1;\n\tvar b = 2;\n</script>",
            '<script>var a=1;var b=2;</script>',
        ];

        yield 'multiple protected literals are restored in order' => [
            '<script>var a = "x    y"; var b = "p    q";</script>',
            '<script>var a="x    y";var b="p    q";</script>',
        ];

        yield 'type attributes other than the default are kept' => [
            '<script type="module">import a from "./a.js"; // drop</script>',
            '<script type="module">import a from"./a.js";</script>',
        ];

        yield 'javascript is squeezed around punctuation' => [
            '<script>var t = `${ `https://x` }`;</script>',
            '<script>var t=`${`https://x`}`;</script>',
        ];

        yield 'declarations are squeezed but string content is untouched' => [
            '<script>var cdn = "//cdn.example.com/a.js";</script>',
            '<script>var cdn="//cdn.example.com/a.js";</script>',
        ];

        yield 'style rules are squeezed' => [
            "<style>\n/* colors */\na { color: red; }\n</style>",
            '<style>a{color:red;}</style>',
        ];

        yield 'a linebreak that carries a semicolon is kept' => [
            "<script>function f(){\nreturn\n1;\n}</script>",
            "<script>function f(){return\n1;}</script>",
        ];

        yield 'space after a keyword is kept' => [
            '<script>var a = typeof x;</script>',
            '<script>var a=typeof x;</script>',
        ];

        yield 'space between two plus signs is kept' => [
            '<script>var b = c + +d;</script>',
            '<script>var b=c+ +d;</script>',
        ];

        yield 'space between two identifiers is kept' => [
            '<script>var x = a/**/b;</script>',
            '<script>var x=a b;</script>',
        ];

        yield 'descendant combinator keeps its space' => [
            '<style>.a .b { top: 0 }</style>',
            '<style>.a .b{top:0}</style>',
        ];

        yield 'calc operators keep their spaces' => [
            '<style>.a{width:calc(100% - 20px)}</style>',
            '<style>.a{width:calc(100% - 20px)}</style>',
        ];
        yield 'whitespace inside pre is content' => [
            "<pre>a    b\n  c</pre>",
            "<pre>a    b\n  c</pre>",
        ];

        yield 'whitespace inside textarea is content' => [
            '<textarea>a    b</textarea>',
            '<textarea>a    b</textarea>',
        ];

        yield 'whitespace inside attribute values is content' => [
            '<img src="x.png" alt="A  B">',
            '<img src="x.png" alt="A  B">',
        ];

        yield 'whitespace inside json-ld string values is content' => [
            '<script type="application/ld+json">{"name":"A  B"}</script>',
            '<script type="application/ld+json">{"name":"A  B"}</script>',
        ];

        yield 'closing bracket inside a script attribute does not split the tag' => [
            '<script data-label="a> b">var x=1;</script>',
            '<script data-label="a> b">var x=1;</script>',
        ];

        yield 'closing bracket inside a style attribute does not split the tag' => [
            '<style data-label="a> b">a{color:red}</style>',
            '<style data-label="a> b">a{color:red}</style>',
        ];

        yield 'closing bracket in an attribute does not shift string boundaries in the body' => [
            '<script data-label="a>b">var s = "  keep  ";</script>',
            '<script data-label="a>b">var s="  keep  ";</script>',
        ];

        yield 'a type inside another attribute value does not classify the script' => [
            '<script data-template="type=text/javascript" type="text/x-template">// keep me</script>',
            '<script data-template="type=text/javascript" type="text/x-template">// keep me</script>',
        ];

        yield 'default type attributes in text content are kept' => [
            '<p>Use type="text/javascript"</p>',
            '<p>Use type="text/javascript"</p>',
        ];

        yield 'default type attribute is removed after other attributes' => [
            '<style media="screen" type="text/css">a{}</style>',
            '<style media="screen">a{}</style>',
        ];

        yield 'space before a numeric property access is kept' => [
            '<script>var s = 1 .toString();</script>',
            '<script>var s=1 .toString();</script>',
        ];

        yield 'a regular expression after a control head is kept' => [
            '<script>if (x) / a /.test(y);</script>',
            '<script>if(x)/ a /.test(y);</script>',
        ];

        yield 'a regular expression after throw is kept' => [
            '<script>throw / a /;</script>',
            '<script>throw/ a /;</script>',
        ];

        yield 'a regular expression after a block is kept' => [
            '<script>if (x) {} / a /.test(y);</script>',
            '<script>if(x){}/ a /.test(y);</script>',
        ];

        yield 'division after a grouped expression is still squeezed' => [
            '<script>var r = (a + b) / c;</script>',
            '<script>var r=(a+b)/c;</script>',
        ];
    }

    #[Test]
    #[DataProvider('contents')]
    public function contentIsMinified(string $content, string $expected): void
    {
        $htmlMinifier = new HtmlMinifier(
            new JavaScriptMinifier(),
            new StyleSheetMinifier(),
            new ContentMask()
        );

        self::assertSame($expected, $htmlMinifier->minify($content));
    }
}
