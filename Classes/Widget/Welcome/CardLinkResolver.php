<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Fills in the concrete href of every {@see CardLink} and the image URI of
 * every {@see ContactCard}.
 *
 * Links pointing at a module route that does not exist are dropped rather than
 * rendered dead, mirroring how the Quick Actions widget handles them.
 */
final readonly class CardLinkResolver
{
    public function __construct(
        private UriBuilder $uriBuilder,
        private SystemResourceFactory $systemResourceFactory,
        private SystemResourcePublisherInterface $systemResourcePublisher,
    ) {
    }

    /**
     * @param list<CardInterface> $cards
     * @return list<CardInterface>
     */
    public function resolveAll(array $cards, ServerRequestInterface $request): array
    {
        return array_map(
            fn (CardInterface $card): CardInterface => match (true) {
                $card instanceof LinksCard => $card->withLinks($this->resolveLinks($card->links)),
                $card instanceof ContactCard && $card->image !== null => $card->withImageUri($this->resourceUri($card->image, $request)),
                default => $card,
            },
            $cards,
        );
    }

    /**
     * Extension resources are published through the system resource API; anything
     * else (an absolute URL, a fileadmin path) is passed through untouched.
     */
    private function resourceUri(string $path, ServerRequestInterface $request): string
    {
        if (!PathUtility::isExtensionPath($path)) {
            return $path;
        }

        return (string)$this->systemResourcePublisher->generateUri(
            $this->systemResourceFactory->createPublicResource($path),
            $request,
        );
    }

    /**
     * @param list<CardLink> $links
     * @return list<CardLink>
     */
    private function resolveLinks(array $links): array
    {
        $resolved = [];
        foreach ($links as $link) {
            $href = $link->url ?? $this->moduleUri($link);
            if ($href !== null) {
                $resolved[] = $link->withHref($href);
            }
        }

        return $resolved;
    }

    private function moduleUri(CardLink $link): ?string
    {
        if ($link->module === null) {
            return null;
        }

        try {
            return (string)$this->uriBuilder->buildUriFromRoute($link->module, $link->parameters);
        } catch (RouteNotFoundException) {
            return null;
        }
    }
}
