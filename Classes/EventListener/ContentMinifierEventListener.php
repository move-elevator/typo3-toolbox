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
    * remove JavaScript inline comments
    * convert linebreaks to spaces
    * convert tabs to spaces
    * convert multiple spaces to one single space
    * remove spaces between tags, but ignore on some inline-tags
    * replace non-HTML5 conform closing tags
    */
    private function minify(string $content): string
    {
        $replacements = [
            '/\/\*\*.\*\//' => ' ',
            '/\n/' => ' ',
            '/\t/' => ' ',
            '/[ ]+/' => ' ',
            '/\>\s\<(?:(?!(?:a|b|strong|img|em|i|span|small|big)[ ]))/' => '><',
            '/" \/>/' => '">',
        ];

        $content = $this->removeUnnecessaryTypeAttributesForStyleAndScriptTags($content);
        $content = $this->removeUnnecessaryWhitespacesFromClassAttributes($content);
        $content = $this->removeUnnecessaryWhitespacesForJsonLdSchemas($content);
        $content = $this->removeCkeditorDataAttributesFromListItems($content);
        $content = $this->removeWhitespacesAfterTagStartAndBeforeTagClose($content);

        return (string)preg_replace(array_keys($replacements), array_values($replacements), $content);
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
