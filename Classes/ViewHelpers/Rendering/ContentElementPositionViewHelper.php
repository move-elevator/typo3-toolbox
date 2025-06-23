<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\ViewHelpers\Rendering;

use MoveElevator\Typo3Toolbox\Domain\Repository\ContentRepository;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentElementPositionViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'Uid from Content Element', true);
        $this->registerArgument('pid', 'int', 'Pid from Content Element', true);
        $this->registerArgument('colPos', 'int', 'colPos from Content Element', true);
        $this->registerArgument('sysLanguageUid', 'int', 'sys_language_uid from Content Element', true);
    }

    public function render(): int
    {
        /** @var ContentRepository $contentRepository */
        $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);

        return $contentRepository->getFirstContentElementId(
            (int)$this->arguments['pid'],
            (int)$this->arguments['uid'],
            (int)$this->arguments['colPos'],
            (int)$this->arguments['sysLanguageUid'],
        );
    }
}
