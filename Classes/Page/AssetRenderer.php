<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Page;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

readonly class AssetRenderer extends \TYPO3\CMS\Core\Page\AssetRenderer
{
    public function renderStyleSheets(bool $priority = false, string $endingSlash = '', ?ConsumableNonce $nonce = null): string
    {
        $inlineStyles = $this->assetCollector->getInlineStyleSheets();
        $normalStyles = $this->assetCollector->getStyleSheets();

        // Remove calculated inline styles from normal styles
        foreach ($inlineStyles as $key => $value) {
            if (true === array_key_exists($key, $normalStyles)) {
                $this->assetCollector->removeStyleSheet($key);
            }
        }

        $return = parent::renderStyleSheets($priority, $endingSlash, $nonce);
        $noScripts = [];
        $styleSheets = $this->assetCollector->getStyleSheets($priority);

        foreach ($styleSheets as $styleSheet) {
            if (true === ($styleSheet['options']['noscript'] ?? false)) {
                $noScripts[] = $this->getAbsoluteWebPath($styleSheet['source']);
            }
        }

        if (count($noScripts) > 0) {
            $return .= '<noscript>';

            foreach ($noScripts as $file) {
                $return .= '<link rel="stylesheet" href="' . $file . '" media="screen">';
            }

            $return .= '</noscript>';
        }

        return $return;
    }

    protected function getAbsoluteWebPath(string $file): string
    {
        if (true === PathUtility::hasProtocolAndScheme($file)) {
            return $file;
        }

        $file = PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($file));

        return GeneralUtility::createVersionNumberedFilename($file);
    }
}
