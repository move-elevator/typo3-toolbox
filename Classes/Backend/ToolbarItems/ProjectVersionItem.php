<?php

namespace MoveElevator\Typo3Toolbox\Backend\ToolbarItems;

use MoveElevator\Typo3Toolbox\Enumeration\Configuration;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

class ProjectVersionItem implements ToolbarItemInterface
{
    /**
    * Checks whether the user has access to this toolbar item
    *
    * @return bool TRUE if user has access, FALSE if not
    */
    public function checkAccess(): bool
    {
        return true;
    }

    /**
    * Render "item" part of this toolbar
    *
    * @return string Toolbar item HTML
    */
    public function getItem(): string
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(Configuration::EXT_KEY->value);
        if (!(bool)($extConf['backendVersionToolbar'] ?? false)) {
            return '';
        }


        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:' . Configuration::EXT_KEY->value . '/Resources/Private/Templates/'],
            partialRootPaths: ['EXT:' . Configuration::EXT_KEY->value . '/Resources/Private/Partials/'],
            layoutRootPaths: ['EXT:' . Configuration::EXT_KEY->value . '/Resources/Private/Layouts/'],
        );

        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        $view = $viewFactory->create($viewFactoryData);
        $view->assignMultiple([
            'version' => $this->getWebsiteVersion(),
        ]);

        return $view->render('ToolbarItems/ProjectVersionItem.html');
    }

    /**
    * Returns the version property from the project's composer.json
    */
    protected function getWebsiteVersion(): string
    {
        $manifest = GeneralUtility::makeInstance(PackageManager::class)->getComposerManifest(Environment::getProjectPath() . '/', true);
        return $manifest?->version ?? '';
    }

    /**
    * TRUE if this toolbar item has a collapsible drop down
    *
    * @return bool
    */
    public function hasDropDown(): bool
    {
        return false;
    }

    /**
    * Render "drop down" part of this toolbar
    *
    * @return string Drop down HTML
    */
    public function getDropDown(): string
    {
        return '';
    }

    /**
    * Returns an array with additional attributes added to containing <li> tag of the item.
    *
    * Typical usages are additional css classes and data-* attributes, classes may be merged
    * with other classes needed by the framework. Do NOT set an id attribute here.
    *
    * array(
    *     'class' => 'my-class',
    *     'data-foo' => '42',
    * )
    *
    * @return array<string, string> List item HTML attributes
    */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
    * Returns an integer between 0 and 100 to determine
    * the position of this item relative to others
    *
    * By default, extensions should return 50 to be sorted between main core
    * items and other items that should be on the very right.
    *
    * @return int 0 .. 100
    */
    public function getIndex(): int
    {
        return 0;
    }
}
