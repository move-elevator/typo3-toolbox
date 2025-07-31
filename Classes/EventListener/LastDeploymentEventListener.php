<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\EventListener;

use MoveElevator\Typo3Toolbox\Enumeration\Configuration;
use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

#[AsEventListener(identifier: 'moveElevator/backend/lastUpdated')]
class LastDeploymentEventListener
{
    public function __invoke(SystemInformationToolbarCollectorEvent $systemInformation): void
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(Configuration::EXT_KEY->value);
        if (!(bool)($extConf['systemInformationLastUpdated'] ?? false)) {
            return;
        }
        $path = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY->value]['systemInformationToolbar']['fileToCheck']);
        if ($path !== '' || !file_exists($path)) {
            return;
        }

        $lastModified = $this->getLastModifiedTime($path);
        if (class_exists(\IntlDateFormatter::class)) {
            $locale = $GLOBALS['TYPO3_CONF_VARS']['SYS']['locale'] ?? \Locale::getDefault();
            $formatter = new \IntlDateFormatter(
                $locale,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'] !== '' ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'] : (date_default_timezone_get() !== '' ? date_default_timezone_get() : 'Europe/Berlin'),
                \IntlDateFormatter::GREGORIAN
            );
            $humanFormatDateTime = $formatter->format($lastModified);
        } else {
            $humanFormatDateTime = date('D, d.m.Y H:i', $lastModified);
        }

        $systemInformation->getToolbarItem()->addSystemInformation(
            $this->getLanguageService()->sL('LLL:EXT:' . Configuration::EXT_KEY->value . '/Resources/Private/Language/locallang_be.xlf:system_information.last_updated'),
            $humanFormatDateTime,
            'actions-refresh',
        );
    }

    private function getLastModifiedTime(string $path): int
    {
        $lastModifiedTime = 0;

        if (!file_exists($path)) {
            return $lastModifiedTime;
        }

        if (is_dir($path)) {
            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iterator as $fileinfo) {
                    if ($fileinfo->isFile() && $fileinfo->getMTime() > $lastModifiedTime) {
                        $lastModifiedTime = $fileinfo->getMTime();
                    }
                }
            } catch (\Exception $e) {
                return filemtime($path) !== false ? filemtime($path) : 0;
            }
        } elseif (is_file($path)) {
            $lastModifiedTime = filemtime($path) !== false ? filemtime($path) : 0;
        }

        return $lastModifiedTime;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
