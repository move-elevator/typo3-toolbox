<?php

use MoveElevator\Typo3Toolbox\Page\AssetRenderer;
use TYPO3\CMS\Core\Page\AssetRenderer as Typo3AssetRenderer;

(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][Typo3AssetRenderer::class] = [
        'className' => AssetRenderer::class,
    ];
})();
