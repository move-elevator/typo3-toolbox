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
        $event->getController()->content = $this->minify( // @phpstan-ignore-line
            $event->getController()->content // @phpstan-ignore-line
        );
    }

    /**
    * remove javascript inline comments
    * convert linebreaks to spaces
    * convert tabs to spaces
    * convert multiple spaces to one single space
    * remove spaces between tags, but ignore on some inline-tags
    * replace non-HTML5 conform closing tags
    * remove type attributes for styles and javascript
    * remove unnecessary whitespaces from class attributes
    * remove unnecessary whitespaces from JSON-LD
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

        $content = str_replace(
            [
                ' type="text/css"',
                ' type=\'text/css\'',
                ' type="text/javascript"',
                ' type=\'text/javascript\'',
            ],
            '',
            $content
        );

        $content = (string)preg_replace_callback(
            '/class="([^"]+)"/',
            static function ($matches) {
                $cleanedClassList = trim(preg_replace('/\s+/', ' ', $matches[1]));
                return 'class="' . $cleanedClassList . '"';
            },
            $content
        );

        $content = (string)preg_replace_callback(
            '/<script\s+type="application\/ld\+json">(.*?)<\/script>/s',
            static function ($matches) {
                $json = trim($matches[1]);
                $minifiedJson = json_encode(json_decode($json, true), JSON_UNESCAPED_SLASHES);

                if ('null' === $minifiedJson) {
                    return '';
                }

                return '<script type="application/ld+json">' . $minifiedJson . '</script>';
            },
            $content
        );

        return preg_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
