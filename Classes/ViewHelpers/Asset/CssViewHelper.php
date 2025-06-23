<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\ViewHelpers\Asset;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

final class CssViewHelper extends AbstractTagBasedViewHelper
{
    protected $escapeChildren = true;
    protected AssetCollector $assetCollector;

    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    public function initialize(): void
    {
        // Add a tag builder, that does not html encode values, because rendering with encoding happens in AssetRenderer
        $this->setTagBuilder(
            new class () extends TagBuilder {
                public function addAttribute($attributeName, $attributeValue, $escapeSpecialCharacters = false): void
                {
                    parent::addAttribute($attributeName, $attributeValue, false);
                }
            }
        );

        parent::initialize();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('disabled', 'bool', 'Define whether or not the described stylesheet should be loaded and applied to the document.');
        $this->registerArgument('useNonce', 'bool', 'Whether to use the global nonce value', false, false);
        $this->registerArgument('identifier', 'string', 'Use this identifier within templates to only inject your CSS once, even though it is added multiple times.', true);
        $this->registerArgument('priority', 'boolean', 'Define whether the CSS should be included before other CSS. CSS will always be output in the <head> tag.', false, false);
        $this->registerArgument('inline', 'bool', 'Define whether or not the referenced file should be loaded as inline styles (Only to be used if \'href\' is set).', false, false);
        $this->registerArgument('noscript', 'boolean', 'Renders a noscript tag with the given file', false, false);
    }

    public function render(): string
    {
        $identifier = (string)$this->arguments['identifier'];
        $attributes = $this->tag->getAttributes();

        // boolean attributes shall output attr="attr" if set
        if ($attributes['disabled'] ?? false) {
            $attributes['disabled'] = 'disabled';
        }

        $file = $attributes['href'] ?? null;
        unset($attributes['href']);
        $options = [
            'priority' => $this->arguments['priority'],
            'useNonce' => $this->arguments['useNonce'],
            'noscript' => (bool)($this->arguments['noscript'] ?? false),
        ];

        if (null !== $file) {
            if ($this->arguments['inline'] ?? false) {
                $content = @file_get_contents(GeneralUtility::getFileAbsFileName(trim($file)));

                if (false !== $content) {
                    $this->assetCollector->addInlineStyleSheet($identifier, $content, $attributes, $options);
                }
            } else {
                $this->assetCollector->addStyleSheet($identifier, $file, $attributes, $options);
            }
        } else {
            $content = (string)$this->renderChildren();

            if ('' !== $content) {
                $this->assetCollector->addInlineStyleSheet($identifier, $content, $attributes, $options);
            }
        }

        return '';
    }
}
