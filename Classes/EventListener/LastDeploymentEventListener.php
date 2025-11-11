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
            $timeZone = 'Europe/Berlin';

            if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone']) && $GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'] !== '') {
                $timeZone = $GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'];
            } elseif (date_default_timezone_get() !== '') {
                $timeZone = date_default_timezone_get();
            }

            $formatter = new \IntlDateFormatter(
                $locale,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                $timeZone,
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
        if (!file_exists($path)) {
            return 0;
        }

        if (is_file($path)) {
            return $this->getFileModificationTime($path);
        }

        if (is_dir($path)) {
            return $this->getDirectoryLastModifiedTime($path);
        }

        return 0;
    }

    private function getFileModificationTime(string $path): int
    {
        $mtime = filemtime($path);
        return $mtime !== false ? $mtime : 0;
    }

    private function getDirectoryLastModifiedTime(string $path): int
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            $lastModifiedTime = 0;
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $lastModifiedTime = max($lastModifiedTime, $fileinfo->getMTime());
                }
            }

            return $lastModifiedTime;
        } catch (\Exception $e) {
            return $this->getFileModificationTime($path);
        }
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
