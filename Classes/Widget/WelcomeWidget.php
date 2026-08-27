<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget;

use MoveElevator\Typo3Toolbox\Widget\Options\WidgetOptionsFactory;
use MoveElevator\Typo3Toolbox\Widget\Welcome\CardLinkResolver;
use MoveElevator\Typo3Toolbox\Widget\Welcome\TimeOfDay;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * Personalized entry point of a project dashboard: a time-of-day greeting for
 * the current backend user, an optional intro text and any number of typed
 * cards (contact, links, custom).
 *
 * Renders with zero configuration.
 */
final class WelcomeWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    private ServerRequestInterface $request;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly WidgetOptionsFactory $optionsFactory,
        private readonly CardLinkResolver $cardLinkResolver,
        private readonly SystemResourceFactory $systemResourceFactory,
        private readonly SystemResourcePublisherInterface $systemResourcePublisher,
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
        $options = $this->optionsFactory->createWelcomeOptions($this->options);

        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'move-elevator/typo3-toolbox']);
        $view->assignMultiple([
            'greetingKey' => TimeOfDay::fromDateTime($this->now())->translationKey(),
            'userName' => $this->userName(),
            'emoji' => $options->emoji,
            'intro' => $options->intro,
            'branding' => $options->branding,
            'brandingLogoUri' => $this->logoUri($options->branding->logo),
            'cards' => $this->cardLinkResolver->resolveAll($options->cards, $this->request),
            'configuration' => $this->configuration,
        ]);

        return $view->render('Widget/WelcomeWidget');
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
     * Extension resources are published through the system resource API; anything
     * else (an absolute URL, a fileadmin path) is passed through untouched.
     */
    private function logoUri(string $logo): string
    {
        if (!PathUtility::isExtensionPath($logo)) {
            return $logo;
        }

        return (string)$this->systemResourcePublisher->generateUri(
            $this->systemResourceFactory->createPublicResource($logo),
            $this->request,
        );
    }

    private function userName(): string
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser instanceof BackendUserAuthentication || !is_array($backendUser->user)) {
            return '';
        }

        $realName = trim((string)($backendUser->user['realName'] ?? ''));

        return $realName !== '' ? $realName : (string)($backendUser->user['username'] ?? '');
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable()->setTimestamp($this->context->getAspect('date')->get('timestamp'));
    }
}
