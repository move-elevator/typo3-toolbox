<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Minifier;

final readonly class HtmlMinifier
{
    /**
     * @var list<string>
     */
    private const array JAVASCRIPT_TYPES = [
        'text/javascript',
        'application/javascript',
        'text/ecmascript',
        'application/ecmascript',
        'module',
    ];

    /**
     * a bare `[^>]*` would end the tag at a `>` inside a quoted value and cut the tag in half
     */
    private const string ATTRIBUTES = '(?:[^>"\']|"[^"]*"|\'[^\']*\')*';

    public function __construct(
        private JavaScriptMinifier $javaScriptMinifier,
        private StyleSheetMinifier $styleSheetMinifier,
        private ContentMask $contentMask,
    ) {
    }

    /**
    * minify inline script and style bodies
    * convert linebreaks to spaces
    * convert tabs to spaces
    * convert multiple spaces to one single space
    * remove spaces between tags, but ignore on some inline-tags
    * remove spaces before tag close
    * remove the redundant self-closing marker from HTML void elements,
    * but keep it in foreign content, where SVG relies on it to close elements
    */
    public function minify(string $content): string
    {
        $replacements = [
            '/\n/' => ' ',
            '/\t/' => ' ',
            '/[ ]+/' => ' ',
            '/\>\s\<(?:(?!(?:a|b|strong|img|em|i|span|small|big)[ ]))/' => '><',
            '/" (\/?)>/' => '"$1>',
            '/(<(?:area|base|br|col|embed|hr|img|input|link|meta|source|track|wbr)\b[^>]*?)\s*\/>/' => '$1>',
        ];

        $content = $this->minifyInlineScriptsAndStyles($content);
        $content = $this->maskRawTextElements($content);
        $content = $this->removeUnnecessaryWhitespacesFromClassAttributes($content);
        $content = $this->removeUnnecessaryWhitespacesForJsonLdSchemas($content);
        $content = $this->removeCkeditorDataAttributesFromListItems($content);
        $content = $this->maskAttributeValues($content);
        $content = $this->removeWhitespacesAfterTagStartAndBeforeTagClose($content);
        $content = (string)preg_replace(array_keys($replacements), array_values($replacements), $content);

        return $this->contentMask->unmask($content);
    }

    private function minifyInlineScriptsAndStyles(string $content): string
    {
        return (string)preg_replace_callback(
            '/<(script|style)\b(' . self::ATTRIBUTES . ')>(.*?)<\/\1\s*>/is',
            function (array $matches): string {
                [$block, $tag, $attributes, $body] = $matches;
                $isStyle = 'style' === strtolower($tag);

                $minifiedBody = match (true) {
                    $isStyle => $this->styleSheetMinifier->minify($body),
                    $this->isJavaScript($attributes) => $this->javaScriptMinifier->minify($body),
                    default => null,
                };

                if (null === $minifiedBody) {
                    return $block;
                }

                return '<' . $tag . $this->removeDefaultTypeAttribute($attributes, $isStyle) . '>'
                    . $this->contentMask->mask($minifiedBody)
                    . '</' . $tag . '>';
            },
            $content
        );
    }

    /**
     * @return array<string, string>
     */
    private function parseAttributes(string $attributes): array
    {
        preg_match_all(
            '/([^\s=\/>]+)(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+))?/',
            $attributes,
            $matches,
            PREG_SET_ORDER
        );

        $parsed = [];

        foreach ($matches as $attribute) {
            $parsed[strtolower($attribute[1])] = trim($attribute[2] ?? '', '"\'');
        }

        return $parsed;
    }

    private function isJavaScript(string $attributes): bool
    {
        $type = $this->parseAttributes($attributes)['type'] ?? null;

        return null === $type || in_array(strtolower(trim($type)), self::JAVASCRIPT_TYPES, true);
    }

    private function removeDefaultTypeAttribute(string $attributes, bool $isStyle): string
    {
        $default = $isStyle ? 'text/css' : 'text/javascript';

        if ($default !== strtolower($this->parseAttributes($attributes)['type'] ?? '')) {
            return $attributes;
        }

        return (string)preg_replace(
            '/(?:^|\s)type\s*=\s*(["\'])' . preg_quote($default, '/') . '\1/i',
            '',
            $attributes
        );
    }

    /**
     * Whitespace is content in these elements, so it has to survive the collapse below.
     */
    private function maskRawTextElements(string $content): string
    {
        return (string)preg_replace_callback(
            '/(<(pre|textarea)\b' . self::ATTRIBUTES . '>)(.*?)(<\/\2\s*>)/is',
            fn (array $matches): string => $matches[1] . $this->contentMask->mask($matches[3]) . $matches[4],
            $content
        );
    }

    /**
     * An attribute value is content too, and a `>` inside one would otherwise end the tag
     * early for every pattern that follows.
     */
    private function maskAttributeValues(string $content): string
    {
        return (string)preg_replace_callback(
            '/<[a-zA-Z][a-zA-Z0-9:-]*' . self::ATTRIBUTES . '\/?>/',
            fn (array $tag): string => (string)preg_replace_callback(
                '/=\s*(["\'])(.*?)\1/s',
                fn (array $value): string => '=' . $value[1] . $this->contentMask->mask($value[2]) . $value[1],
                $tag[0]
            ),
            $content
        );
    }

    /**
     * @see https://forge.typo3.org/issues/109002
     * @see https://github.com/ckeditor/ckeditor5/issues/19006
     */
    private function removeCkeditorDataAttributesFromListItems(string $content): string
    {
        return (string)preg_replace(
            '/(<li)\s+data-list-item-id="[^"]*"/',
            '$1',
            $content
        );
    }

    private function removeWhitespacesAfterTagStartAndBeforeTagClose(string $content): string
    {
        return (string)preg_replace_callback(
            '/<(h[1-6]|p|li|td|th|dt|dd|button|label)[^>]*>\K\s+|\s+(?=<\/(h[1-6]|p|li|td|th|dt|dd|button|label)>)/',
            static fn () => '',
            $content
        );
    }

    private function removeUnnecessaryWhitespacesFromClassAttributes(string $content): string
    {
        return (string)preg_replace_callback(
            '/class="([^"]+)"/',
            static function (array $matches) {
                $cleanedClassList = trim((string)preg_replace('/\s+/', ' ', $matches[1]));
                return 'class="' . $cleanedClassList . '"';
            },
            $content
        );
    }

    private function removeUnnecessaryWhitespacesForJsonLdSchemas(string $content): string
    {
        return (string)preg_replace_callback(
            '/<script\s+type="application\/ld\+json">(.*?)<\/script>/s',
            function (array $matches): string {
                $json = trim($matches[1]);

                try {
                    $minifiedJson = json_encode(json_decode($json, true), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                } catch (\JsonException) {
                    return '';
                }

                if ('null' === $minifiedJson) {
                    return '';
                }

                return '<script type="application/ld+json">' . $this->contentMask->mask($minifiedJson) . '</script>';
            },
            $content
        );
    }
}
