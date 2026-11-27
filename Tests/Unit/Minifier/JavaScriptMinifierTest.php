<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Minifier;

use MoveElevator\Typo3Toolbox\Minifier\JavaScriptMinifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class JavaScriptMinifierTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function scripts(): \Generator
    {
        yield 'line comment' => [
            "var a = 1; // note\nvar b = 2;",
            'var a=1;var b=2;',
        ];

        yield 'block comment spanning lines keeps asi linebreak' => [
            "a = b\n/* x\n */\nc()",
            "a=b\nc()",
        ];

        yield 'asi linebreak after return value' => [
            "return\nvalue",
            "return\nvalue",
        ];

        yield 'asi linebreak between statements' => [
            "let a = 1\nlet b = 2",
            "let a=1\nlet b=2",
        ];

        yield 'linebreak before operator is dropped' => [
            "a = b\n+ c",
            "a=b\n+c",
        ];

        yield 'plus plus is not merged' => [
            'a + +b; c - -d;',
            'a+ +b;c- -d;',
        ];

        yield 'number before member access keeps space' => [
            '1 .toString()',
            '1 .toString()',
        ];

        yield 'identifiers keep a space' => [
            'const  x  =  typeof  y;',
            'const x=typeof y;',
        ];

        yield 'division' => [
            'x = a / b / c;',
            'x=a/b/c;',
        ];

        yield 'regular expression after return' => [
            'return /a\\/b/g.test(s);',
            'return/a\\/b/g.test(s);',
        ];

        yield 'regular expression after control head' => [
            'if (x) /re/.test(y);',
            'if(x)/re/.test(y);',
        ];

        yield 'division after closing parenthesis' => [
            'y = (a) / 2;',
            'y=(a)/2;',
        ];

        yield 'regular expression with character class' => [
            'r = /[/\\]]+/;',
            'r=/[/\\]]+/;',
        ];

        yield 'regular expression after keyword in window' => [
            'x = typeof /a/;',
            'x=typeof/a/;',
        ];

        yield 'increment before slash is a division' => [
            'i++ / 2',
            'i++/2',
        ];

        yield 'strings with escapes' => [
            's = \'it\\\'s\' + "a \\" b";',
            's=\'it\\\'s\'+"a \\" b";',
        ];

        yield 'template literal with interpolation' => [
            't = `a ${ b  +  c } d`;',
            't=`a ${b+c} d`;',
        ];

        yield 'nested template literal' => [
            't = `a ${ `b ${ c }` } d`;',
            't=`a ${`b ${c}`} d`;',
        ];

        yield 'interpolation with braces and strings' => [
            't = `${ {a: \'}\'}.a }`;',
            't=`${{a:\'}\'}.a}`;',
        ];

        yield 'interpolation with comment' => [
            't = `${ a /* } */ }`;',
            't=`${a}`;',
        ];

        yield 'unterminated template literal' => [
            't = `abc',
            't=`abc',
        ];

        yield 'unterminated block comment' => [
            'a = 1; /* open',
            'a=1;',
        ];

        yield 'line comment at end without newline' => [
            'a = 1; // end',
            'a=1;',
        ];

        yield 'carriage return linebreak' => [
            "a = 1\r\nb = 2",
            "a=1\nb=2",
        ];

        yield 'unicode identifiers keep space' => [
            'var ä = 1; return ä',
            'var ä=1;return ä',
        ];

        yield 'object and array' => [
            'o = { a : [ 1 , 2 ] , \'b\' : 3 };',
            'o={a:[1,2],\'b\':3};',
        ];

        yield 'arrow function' => [
            'f = ( a ) => { return a * 2 }',
            'f=(a)=>{return a*2}',
        ];

        yield 'while head' => [
            'while (i < 3) /x/.exec(s)',
            'while(i<3)/x/.exec(s)',
        ];

        yield 'empty input' => [
            '',
            '',
        ];

        yield 'only whitespace' => [
            "  \n\t ",
            '',
        ];
    }

    #[Test]
    #[DataProvider('scripts')]
    public function minifyProducesTheExpectedCode(string $code, string $expected): void
    {
        self::assertSame($expected, new JavaScriptMinifier()->minify($code));
    }
}
