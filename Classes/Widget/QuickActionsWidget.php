<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget;

use MoveElevator\Typo3Toolbox\Widget\Options\WidgetOptionsFactory;
use MoveElevator\Typo3Toolbox\Widget\QuickActions\QuickActionResolver;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * A configurable shortcut list for the recurring editor workflows of a project.
 */
final class QuickActionsWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    private ServerRequestInterface $request;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly QuickActionResolver $actionResolver,
        private readonly WidgetOptionsFactory $optionsFactory,
        private readonly array $options = [],
    ) {
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $options = $this->optionsFactory->createQuickActionsOptions($this->options);

        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'move-elevator/typo3-toolbox']);
        $view->assignMultiple([
            'actions' => $this->actionResolver->resolve($options),
            'configuration' => $this->configuration,
        ]);

        return $view->render('Widget/QuickActionsWidget');
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return list<string>
     */
    public function getCssFiles(): array
    {
        return ['EXT:typo3_toolbox/Resources/Public/Css/Widgets.css'];
    }
}
