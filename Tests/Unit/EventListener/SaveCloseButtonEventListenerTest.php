<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\EventListener;

use MoveElevator\Typo3Toolbox\EventListener\SaveCloseButtonEventListener;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SaveCloseButtonEventListenerTest extends UnitTestCase
{
    #[Test]
    public function javaScriptModuleIsNotLoadedWithoutSaveButton(): void
    {
        // SaveAndClose.js imports form-engine.js, which throws outside of edit forms
        // because TYPO3.settings.FormEngine.formName is only set by FormResultCompiler.
        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('loadJavaScriptModule');

        $listener = new SaveCloseButtonEventListener(
            self::createStub(ComponentFactory::class),
            self::createStub(IconFactory::class),
            $pageRenderer,
        );

        $listener(new ModifyButtonBarEvent(
            [],
            self::createStub(ButtonBar::class),
            self::createStub(ServerRequestInterface::class),
        ));
    }
}
