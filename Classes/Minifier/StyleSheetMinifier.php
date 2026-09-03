<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Minifier;

final class StyleSheetMinifier
{
    private const string QUOTED_LITERAL_PATTERN = '/(["\'])(?:\\\\[\s\S]|(?!\1)[^\\\\\n])*\1/A';

    /**
     * Whitespace is only dropped around punctuation that cannot be a descendant combinator,
     * so `.a .b` keeps its space and `calc(100% - 20px)` keeps its operators.
     */
    private const string DROP_AFTER = '{};,:';

    private const string DROP_BEFORE = '{};,';

    public function minify(string $style): string
    {
        $length = strlen($style);
        $result = '';
        $position = 0;
        $pendingWhitespace = false;

        while ($position < $length) {
            $skipped = $this->readComment($style, $position) ?? $this->readWhitespace($style, $position);

            if (null !== $skipped) {
                $position = $skipped;
                $pendingWhitespace = true;
                continue;
            }

            if ($pendingWhitespace && '' !== $result && $this->needsSpace($result, $style[$position])) {
                $result .= ' ';
            }

            $pendingWhitespace = false;

            if (('"' === $style[$position] || "'" === $style[$position])
                && 1 === preg_match(self::QUOTED_LITERAL_PATTERN, $style, $matches, 0, $position)
            ) {
                $result .= $matches[0];
                $position += strlen($matches[0]);
                continue;
            }

            $result .= $style[$position];
            ++$position;
        }

        return $result;
    }

    private function readWhitespace(string $style, int $position): ?int
    {
        $run = strspn($style, " \t\n\r", $position);

        return 0 === $run ? null : $position + $run;
    }

    private function readComment(string $style, int $position): ?int
    {
        if ('/' !== $style[$position] || '*' !== ($style[$position + 1] ?? '')) {
            return null;
        }

        $end = strpos($style, '*/', $position + 2);

        return false === $end ? strlen($style) : $end + 2;
    }

    private function needsSpace(string $precedingCode, string $nextCharacter): bool
    {
        $previousCharacter = $precedingCode[strlen($precedingCode) - 1];

        return !str_contains(self::DROP_AFTER, $previousCharacter)
            && !str_contains(self::DROP_BEFORE, $nextCharacter);
    }
}
