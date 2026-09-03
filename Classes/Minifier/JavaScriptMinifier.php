<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Minifier;

final class JavaScriptMinifier
{
    /**
     * @var list<string>
     */
    private const array REGULAR_EXPRESSION_KEYWORDS = [
        'return',
        'throw',
        'typeof',
        'case',
        'in',
        'of',
        'delete',
        'void',
        'instanceof',
        'new',
        'do',
        'else',
        'yield',
        'await',
    ];

    private const int PRECEDING_TOKEN_WINDOW = 32;

    /**
     * Misreading a division as a regular expression only leaves the text unsqueezed, while the
     * reverse squeezes the pattern itself, so anything not clearly a division stays a literal.
     */
    private const string EXPRESSION_END_PATTERN = '/(?:[\w$\]]|\+\+|--)$/';

    private const string CONTROL_KEYWORD_PATTERN = '/(?:^|[^\w$])(?:if|for|while|switch|catch|with)\s*$/';

    private const string QUOTED_LITERAL_PATTERN = '/(["\'])(?:\\\\[\s\S]|(?!\1)[^\\\\\n])*\1/A';

    private const string REGULAR_EXPRESSION_PATTERN = '/\/(?:\\\\.|\[(?:\\\\.|[^\]\\\\\n])*\]|[^\/\\\\\n\[])+\/[a-z]*/A';

    private const string IDENTIFIER_CHARACTER_PATTERN = '/[\w$\\\\\x80-\xff]/';

    private const string STATEMENT_END_PATTERN = '/[\w$)\]}\'"`]/';

    private const string STATEMENT_START_PATTERN = '/[\w$({\[+\-!~\'"`\/]/';

    public function minify(string $code): string
    {
        $length = strlen($code);
        $result = '';
        $position = 0;
        $pendingLinebreak = null;
        $controlParentheses = [];
        $followsControlHead = false;

        while ($position < $length) {
            $skipped = $this->readComment($code, $position) ?? $this->readWhitespace($code, $position);

            if (null !== $skipped) {
                [$position, $spansLines] = $skipped;
                $pendingLinebreak = ($pendingLinebreak ?? false) || $spansLines;
                continue;
            }

            if (null !== $pendingLinebreak && '' !== $result) {
                $result .= $this->separator($result, $code[$position], $pendingLinebreak);
            }

            $pendingLinebreak = null;
            $literal = $this->readLiteral($code, $position, $result, $followsControlHead);

            if (null !== $literal) {
                [$text, $position] = $literal;
                $result .= $text;
                $followsControlHead = false;
                continue;
            }

            $character = $code[$position];

            if ('(' === $character) {
                $controlParentheses[] = 1 === preg_match(self::CONTROL_KEYWORD_PATTERN, $result);
            }

            // a statement follows a control head, so a slash starting it opens a literal
            $followsControlHead = ')' === $character && true === array_pop($controlParentheses);
            $result .= $character;
            ++$position;
        }

        return $result;
    }

    /**
     * @return array{int, bool}|null
     */
    private function readWhitespace(string $code, int $position): ?array
    {
        $run = strspn($code, " \t\n\r", $position);

        if (0 === $run) {
            return null;
        }

        $whitespace = substr($code, $position, $run);

        return [$position + $run, str_contains($whitespace, "\n") || str_contains($whitespace, "\r")];
    }

    /**
     * @return array{int, bool}|null
     */
    private function readComment(string $code, int $position): ?array
    {
        if ('/' !== $code[$position]) {
            return null;
        }

        $nextCharacter = $code[$position + 1] ?? '';

        if ('*' === $nextCharacter) {
            $end = strpos($code, '*/', $position + 2);
            $end = false === $end ? strlen($code) : $end + 2;

            return [$end, str_contains(substr($code, $position, $end - $position), "\n")];
        }

        if ('/' === $nextCharacter) {
            $end = strpos($code, "\n", $position);

            return [false === $end ? strlen($code) : $end, false];
        }

        return null;
    }

    /**
     * A linebreak that automatic semicolon insertion relies on has to survive, otherwise
     * `return` and its value end up on one line and the script changes meaning.
     */
    private function separator(string $precedingCode, string $nextCharacter, bool $spansLines): string
    {
        $previousCharacter = $precedingCode[strlen($precedingCode) - 1];

        if ($spansLines
            && 1 === preg_match(self::STATEMENT_END_PATTERN, $previousCharacter)
            && 1 === preg_match(self::STATEMENT_START_PATTERN, $nextCharacter)
        ) {
            return "\n";
        }

        return $this->needsSpace($previousCharacter, $nextCharacter) ? ' ' : '';
    }

    /**
     * Dropping the space would merge two identifiers, or turn `a + +b` into an increment.
     */
    private function needsSpace(string $previousCharacter, string $nextCharacter): bool
    {
        if (('+' === $previousCharacter || '-' === $previousCharacter) && $previousCharacter === $nextCharacter) {
            return true;
        }

        // `1 .toString()` would collapse into the invalid numeric literal `1.toString()`
        if ('.' === $nextCharacter && 1 === preg_match('/\d/', $previousCharacter)) {
            return true;
        }

        return 1 === preg_match(self::IDENTIFIER_CHARACTER_PATTERN, $previousCharacter)
            && 1 === preg_match(self::IDENTIFIER_CHARACTER_PATTERN, $nextCharacter);
    }

    /**
     * @return array{string, int}|null
     */
    private function readLiteral(string $code, int $position, string $precedingCode, bool $followsControlHead): ?array
    {
        $character = $code[$position];

        if ('`' === $character) {
            return $this->readTemplateLiteral($code, $position);
        }

        $opensRegularExpression = $followsControlHead || $this->slashOpensRegularExpression($precedingCode);

        $pattern = match (true) {
            '"' === $character, "'" === $character => self::QUOTED_LITERAL_PATTERN,
            '/' === $character && $opensRegularExpression => self::REGULAR_EXPRESSION_PATTERN,
            default => null,
        };

        if (null === $pattern || 1 !== preg_match($pattern, $code, $matches, 0, $position)) {
            return null;
        }

        return [$matches[0], $position + strlen($matches[0])];
    }

    /**
     * Only the text of a template literal is content; an interpolation holds code and is
     * minified like any other expression.
     *
     * @return array{string, int}
     */
    private function readTemplateLiteral(string $code, int $position): array
    {
        $length = strlen($code);
        $result = '`';
        ++$position;

        while ($position < $length) {
            $character = $code[$position];

            if ('\\' === $character) {
                $result .= substr($code, $position, 2);
                $position += 2;
                continue;
            }

            if ('`' === $character) {
                ++$position;

                return [$result . '`', $position];
            }

            if ('$' === $character && '{' === ($code[$position + 1] ?? '')) {
                $end = $this->findInterpolationEnd($code, $position + 2);
                $result .= '${' . $this->minify(substr($code, $position + 2, $end - $position - 2)) . '}';
                $position = $end + 1;
                continue;
            }

            $result .= $character;
            ++$position;
        }

        return [$result, $position];
    }

    private function findInterpolationEnd(string $code, int $position): int
    {
        $length = strlen($code);
        $depth = 1;

        while ($position < $length) {
            $comment = $this->readComment($code, $position);

            if (null !== $comment) {
                $position = $comment[0];
                continue;
            }

            $literal = $this->readLiteral($code, $position, substr($code, 0, $position), false);

            if (null !== $literal) {
                $position = $literal[1];
                continue;
            }

            $depth += match ($code[$position]) {
                '{' => 1,
                '}' => -1,
                default => 0,
            };

            if (0 === $depth) {
                return $position;
            }

            ++$position;
        }

        return $length;
    }

    private function slashOpensRegularExpression(string $precedingCode): bool
    {
        $precedingCode = rtrim(substr($precedingCode, -self::PRECEDING_TOKEN_WINDOW));

        if (1 !== preg_match(self::EXPRESSION_END_PATTERN, $precedingCode)) {
            return true;
        }

        return 1 === preg_match(
            '/(?:^|[^\w$])(?:' . implode('|', self::REGULAR_EXPRESSION_KEYWORDS) . ')$/',
            $precedingCode
        );
    }
}
