<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget;

use MoveElevator\Typo3Toolbox\Widget\EndOfLife\DatabasePlatformDetector;
use MoveElevator\Typo3Toolbox\Widget\EndOfLife\LifecycleDataProvider;
use MoveElevator\Typo3Toolbox\Widget\EndOfLife\TimelineFactory;
use MoveElevator\Typo3Toolbox\Widget\Options\ComponentRequest;
use MoveElevator\Typo3Toolbox\Widget\Options\EndOfLifeOptions;
use MoveElevator\Typo3Toolbox\Widget\Options\WidgetOptionsFactory;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * Admin-facing lifecycle overview: one segmented timeline bar per component on a
 * shared time axis with a "today" marker, including TYPO3 ELTS awareness.
 *
 * The widget has no hard permission check; restrict it via the dashboard widget
 * permissions of the backend groups (see README).
 */
final class EndOfLifeWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    private ServerRequestInterface $request;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly LifecycleDataProvider $lifecycleDataProvider,
        private readonly DatabasePlatformDetector $databasePlatformDetector,
        private readonly TimelineFactory $timelineFactory,
        private readonly WidgetOptionsFactory $optionsFactory,
        private readonly Context $context,
        private readonly array $options = [],
    ) {
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $now = $this->now();
        $options = $this->optionsFactory->createEndOfLifeOptions($this->options, $now);

        $components = [];
        foreach ($this->collectComponentRequests($options) as $request) {
            $lifecycle = $this->lifecycleDataProvider->resolve($request, $now);
            if ($lifecycle !== null) {
                $components[] = $lifecycle;
            }
        }

        $timeline = $this->timelineFactory->build($components, $options->timeWindow, $now, $options->warningThresholdDays);

        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'move-elevator/typo3-toolbox']);
        $view->assignMultiple([
            'timeline' => $timeline,
            'window' => $options->timeWindow,
            'configuration' => $this->configuration,
        ]);

        return $view->render('Widget/EndOfLifeWidget');
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

    /**
     * Auto-detects TYPO3, PHP and the database platform (unless overridden) and
     * appends the configured components.
     *
     * @return list<ComponentRequest>
     */
    private function collectComponentRequests(EndOfLifeOptions $options): array
    {
        $configured = array_map(
            static fn (ComponentRequest $component): string => strtolower($component->product),
            $options->components,
        );

        $detected = [
            new ComponentRequest('typo3', new Typo3Version()->getBranch(), false, 'TYPO3'),
            new ComponentRequest('php', PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, false, 'PHP'),
            $this->databasePlatformDetector->detect(),
        ];

        $requests = [];
        foreach ($detected as $request) {
            if ($request !== null && !in_array(strtolower($request->product), $configured, true)) {
                $requests[] = $request;
            }
        }

        return [...$requests, ...$options->components];
    }

    private function now(): \DateTimeImmutable
    {
        $timestamp = $this->context->getAspect('date')->get('timestamp');

        return new \DateTimeImmutable()->setTimestamp($timestamp);
    }
}
