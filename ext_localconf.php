<?php

use MoveElevator\Typo3Toolbox\Enumeration\Configuration;
use MoveElevator\Typo3Toolbox\Page\AssetRenderer;
use Networkteam\SentryClient;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\AssetRenderer as Typo3AssetRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][Typo3AssetRenderer::class] = [
        'className' => AssetRenderer::class,
    ];

    $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(Configuration::EXT_KEY->value);
    if ((bool)($extConf['sentryBackendEnabled'] ?? false)) {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
            LogLevel::ERROR => [
                SentryClient\SentryLogWriter::class => [],
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = SentryClient\DebugExceptionHandler::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = SentryClient\ProductionExceptionHandler::class;
    }
})();
