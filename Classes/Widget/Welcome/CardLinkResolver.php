<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Welcome;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;

/**
 * Fills in the concrete href of every {@see CardLink}.
 *
 * Links pointing at a module route that does not exist are dropped rather than
 * rendered dead, mirroring how the Quick Actions widget handles them.
 */
final readonly class CardLinkResolver
{
    public function __construct(
        private UriBuilder $uriBuilder,
    ) {
    }

    /**
     * @param list<CardInterface> $cards
     * @return list<CardInterface>
     */
    public function resolveAll(array $cards): array
    {
        return array_map(
            fn (CardInterface $card): CardInterface => $card instanceof LinksCard
                ? $card->withLinks($this->resolveLinks($card->links))
                : $card,
            $cards,
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
