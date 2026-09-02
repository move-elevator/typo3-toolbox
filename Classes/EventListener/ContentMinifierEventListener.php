<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

#[AsEventListener(identifier: 'moveElevator/contentMinifier')]
final class ContentMinifierEventListener
{
    public function __invoke(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $event->setContent($this->minify($event->getContent()));
    }

    /**
    * keep <script> and <style> contents untouched, so a JavaScript `//` line comment doesn't
    * lose its line terminator and swallow the following code
    * convert linebreaks to spaces
    * convert tabs to spaces
    * convert multiple spaces to one single space
    * remove spaces between tags, but ignore on some inline-tags
    * remove spaces before tag close
    * remove the redundant self-closing marker from HTML void elements,
    * but keep it in foreign content, where SVG relies on it to close elements
    */
    private function minify(string $content): string
    {
        $replacements = [
            '/\/\*\*.\*\//' => ' ',
            '/\n/' => ' ',
            '/\t/' => ' ',
            '/[ ]+/' => ' ',
            '/\>\s\<(?:(?!(?:a|b|strong|img|em|i|span|small|big)[ ]))/' => '><',
            '/" (\/?)>/' => '"$1>',
            '/(<(?:area|base|br|col|embed|hr|img|input|link|meta|source|track|wbr)\b[^>]*?)\s*\/>/' => '$1>',
        ];

        $content = $this->removeUnnecessaryTypeAttributesForStyleAndScriptTags($content);
        $content = $this->removeUnnecessaryWhitespacesFromClassAttributes($content);
        $content = $this->removeUnnecessaryWhitespacesForJsonLdSchemas($content);
        $content = $this->removeCkeditorDataAttributesFromListItems($content);
        $content = $this->removeWhitespacesAfterTagStartAndBeforeTagClose($content);

        [$content, $preservedScriptAndStyleContents] = $this->extractScriptAndStyleContents($content);
        $content = (string)preg_replace(array_keys($replacements), array_values($replacements), $content);

        return strtr($content, $preservedScriptAndStyleContents);
    }

    /**
     * Protects the raw content of <script> and <style> tags from the newline/tab/space-collapsing
     * replacements in minify(), since that would swallow the line terminator of a JavaScript `//`
     * line comment and corrupt everything after it.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function extractScriptAndStyleContents(string $content): array
    {
        $preservedContents = [];
        $nonce = bin2hex(random_bytes(8));

        $content = (string)preg_replace_callback(
            '/(<(script|style)\b[^>]*>)(.*?)(<\/\2>)/s',
            static function (array $matches) use (&$preservedContents, $nonce) {
                $placeholder = "\x00{$nonce}" . count($preservedContents) . "\x00";
                $preservedContents[$placeholder] = $matches[3];

                return $matches[1] . $placeholder . $matches[4];
            },
            $content
        );

        return [$content, $preservedContents];
    }

    private function removeUnnecessaryTypeAttributesForStyleAndScriptTags(string $content): string
    {
        return str_replace(
            [
                ' type="text/css"',
                ' type=\'text/css\'',
                ' type="text/javascript"',
                ' type=\'text/javascript\'',
            ],
            '',
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
            static function (array $matches) {
                $json = trim($matches[1]);

                try {
                    $minifiedJson = json_encode(json_decode($json, true), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                } catch (\JsonException) {
                    return '';
                }

                if ('null' === $minifiedJson) {
                    return '';
                }

                return '<script type="application/ld+json">' . $minifiedJson . '</script>';
            },
            $content
        );
    }
}
