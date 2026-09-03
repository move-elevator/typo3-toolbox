<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Minifier;

/**
 * The HTML rules must not touch an already minified script or style body again,
 * so it is carried through them encoded as a marker that holds no markup of its own.
 */
final class ContentMask
{
    public function mask(string $content): string
    {
        if ('' === $content) {
            return $content;
        }

        // NUL never occurs in rendered markup and is matched by none of the replacements
        return "\0" . bin2hex($content) . "\0";
    }

    public function unmask(string $content): string
    {
        return (string)preg_replace_callback(
            '/\0([0-9a-f]*)\0/',
            static fn (array $matches): string => (string)hex2bin($matches[1]),
            $content
        );
    }
}
