<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\EventListener;

use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\{ButtonBar,ComponentFactory,ModifyButtonBarEvent};
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\{IconFactory,IconSize};
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;

#[AsEventListener(identifier: 'moveElevator/modifyButtonBar')]
final readonly class SaveCloseButtonEventListener
{
    public function __construct(
        private ComponentFactory $componentFactory,
        private IconFactory $iconFactory,
        private PageRenderer $pageRenderer,
    ) {
    }

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $buttons = $event->getButtons();
        $saveButton = $buttons[ButtonBar::BUTTON_POSITION_LEFT][2][0] ?? null;

        if ($saveButton instanceof InputButton) {
            $language = $this->getLanguageService();
            $title = 'save';

            if ($language instanceof LanguageService) {
                $title = $language->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc');
            }

            $saveCloseButton = $this->componentFactory->createInputButton()
                ->setName('_saveandclosedok')
                ->setValue('1')
                ->setForm($saveButton->getForm())
                ->setDataAttributes([
                    'js' => 'save-and-close-button',
                ])
                ->setTitle($title)
                ->setIcon($this->iconFactory->getIcon('actions-document-save-close', IconSize::SMALL))
                ->setShowLabelText(true);

            $buttons[ButtonBar::BUTTON_POSITION_LEFT][2][] = $saveCloseButton;
        }

        $this->pageRenderer->loadJavaScriptModule('@move-elevator/typo3-toolbox/SaveAndClose.js');

        $event->setButtons($buttons);
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
