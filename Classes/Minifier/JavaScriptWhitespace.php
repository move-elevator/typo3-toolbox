<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Minifier;

/**
 * Whitespace and comments between JavaScript tokens, and what has to remain of them.
 */
final class JavaScriptWhitespace
{
    private const string IDENTIFIER_CHARACTER_PATTERN = '/[\w$\\\\\x80-\xff]/';

    private const string STATEMENT_END_PATTERN = '/[\w$)\]}\'"`]/';

    private const string STATEMENT_START_PATTERN = '/[\w$({\[+\-!~\'"`\/]/';

    /**
     * @return array{int, bool}|null the position after the comment or whitespace run, and whether it spans lines
     */
    public function skip(string $code, int $position): ?array
    {
        return $this->readComment($code, $position) ?? $this->readWhitespace($code, $position);
    }

    /**
     * A linebreak that automatic semicolon insertion relies on has to survive, otherwise
     * `return` and its value end up on one line and the script changes meaning.
     *
     * @param bool|null $spansLines null when nothing was skipped between the two tokens
     */
    public function separator(string $precedingCode, string $nextCharacter, ?bool $spansLines): string
    {
        if (null === $spansLines || '' === $precedingCode) {
            return '';
        }

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
}
