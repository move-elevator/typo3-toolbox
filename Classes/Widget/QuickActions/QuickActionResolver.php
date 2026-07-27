<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\QuickActions;

use MoveElevator\Typo3Toolbox\Widget\Options\QuickAction;
use MoveElevator\Typo3Toolbox\Widget\Options\QuickActionsOptions;
use MoveElevator\Typo3Toolbox\Widget\Options\QuickActionType;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Turns configured Quick Actions into concrete links for the current backend user.
 *
 * Actions the user may not see (via `beGroups`) are dropped, and misconfigured
 * actions — unknown record tables or unresolvable module routes — are skipped
 * silently so a single mistake never breaks the whole widget.
 */
final class QuickActionResolver
{
    private const DEFAULT_ICONS = [
        QuickActionType::Url->value => 'actions-link',
        QuickActionType::Module->value => 'actions-open',
        QuickActionType::Record->value => 'actions-plus',
    ];

    public function __construct(
        private readonly UriBuilder $uriBuilder,
    ) {
    }

    /**
     * @return list<ResolvedAction>
     */
    public function resolve(QuickActionsOptions $options): array
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser instanceof BackendUserAuthentication) {
            return [];
        }

        $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('dashboard');
        $resolved = [];
        foreach ($options->actions as $action) {
            if (!$this->isVisibleFor($action, $backendUser)) {
                continue;
            }
            $url = $this->resolveUrl($action, $returnUrl);
            if ($url === null) {
                continue;
            }
            $resolved[] = new ResolvedAction(
                label: $action->label,
                iconIdentifier: $action->iconIdentifier ?? self::DEFAULT_ICONS[$action->type->value],
                url: $url,
                external: $action->type === QuickActionType::Url,
            );
        }

        return $resolved;
    }

    private function isVisibleFor(QuickAction $action, BackendUserAuthentication $backendUser): bool
    {
        if ($action->beGroups === [] || $backendUser->isAdmin()) {
            return true;
        }

        $userGroups = array_map(intval(...), $backendUser->userGroupsUID);
        foreach ($action->beGroups as $group) {
            if (in_array((int)$group, $userGroups, true)) {
                return true;
            }
        }

        return false;
    }

    private function resolveUrl(QuickAction $action, string $returnUrl): ?string
    {
        return match ($action->type) {
            QuickActionType::Url => $action->url,
            QuickActionType::Module => $this->resolveModuleUrl($action),
            QuickActionType::Record => $this->resolveRecordUrl($action, $returnUrl),
        };
    }

    private function resolveModuleUrl(QuickAction $action): ?string
    {
        try {
            return (string)$this->uriBuilder->buildUriFromRoute((string)$action->module, $action->moduleParameters);
        } catch (RouteNotFoundException) {
            return null;
        }
    }

    private function resolveRecordUrl(QuickAction $action, string $returnUrl): ?string
    {
        $table = (string)$action->recordTable;
        if (!isset($GLOBALS['TCA'][$table])) {
            return null;
        }

        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [$table => [$action->recordPid ?? 0 => 'new']],
            'returnUrl' => $returnUrl,
        ]);
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;

        return $backendUser instanceof BackendUserAuthentication ? $backendUser : null;
    }
}
