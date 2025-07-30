<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\EventListener;

use MoveElevator\Typo3Toolbox\Enumeration\Configuration;
use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsEventListener(identifier: 'moveElevator/backend/lastUpdated')]
class LastDeploymentEventListener
{
    public function __invoke(SystemInformationToolbarCollectorEvent $systemInformation): void
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(Configuration::EXT_KEY->value);
        if ((bool)($extConf['systemInformationLastUpdated'] ?? false)) {
            return;
        }
        $path = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY->value]['systemInformationToolbar']['fileToCheck']);
        $lastModified = $this->getLastModifiedTime($path);
        if (class_exists(\IntlDateFormatter::class)) {
            $formatter = new \IntlDateFormatter(
                'de-DE',
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'] ?? date_default_timezone_get(),
                \IntlDateFormatter::GREGORIAN
            );
            $humanFormatDateTime = $formatter->format($lastModified);
        } else {
            $humanFormatDateTime = date('D, d.m.Y H:i', $lastModified);
        }

        $systemInformation->getToolbarItem()->addSystemInformation(
            $this->getLanguageService()->sL('LLL:EXT:' . Configuration::EXT_KEY ->value. '/Resources/Private/Language/locallang_be.xlf:system_information.last_updated'),
            $humanFormatDateTime,
            'actions-refresh',
        );
    }

    private function getLastModifiedTime(string $path): int
    {
        $lastModifiedTime = 0;

        if (is_dir($path)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile() && $fileinfo->getMTime() > $lastModifiedTime) {
                    $lastModifiedTime = $fileinfo->getMTime();
                }
            }
        } elseif (is_file($path)) {
            $lastModifiedTime = filemtime($path);
        }

        return $lastModifiedTime;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
