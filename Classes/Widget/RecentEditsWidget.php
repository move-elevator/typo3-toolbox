<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget;

use MoveElevator\Typo3Toolbox\Widget\Options\WidgetOptionsFactory;
use MoveElevator\Typo3Toolbox\Widget\RecentEdits\RecentEditsDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * "Where was I?" — lists the records the current backend user edited most
 * recently, each linking straight back into its edit form.
 */
final class RecentEditsWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    private ServerRequestInterface $request;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly RecentEditsDataProvider $dataProvider,
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
        $options = $this->optionsFactory->createRecentEditsOptions($this->options);

        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'move-elevator/typo3-toolbox']);
        $view->assignMultiple([
            'edits' => $this->dataProvider->findRecentEdits($options),
            'configuration' => $this->configuration,
        ]);

        return $view->render('Widget/RecentEditsWidget');
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
