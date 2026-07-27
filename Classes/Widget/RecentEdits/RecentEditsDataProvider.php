<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\RecentEdits;

use Doctrine\DBAL\ParameterType;
use MoveElevator\Typo3Toolbox\Widget\Options\RecentEditsOptions;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Collects the records the current backend user edited most recently from
 * `sys_history`, grouped per record (latest change wins).
 *
 * Records the user can no longer reach are skipped rather than rendered as dead
 * links: unknown TCA tables, deleted records and tables the user may not modify
 * are filtered out.
 */
final class RecentEditsDataProvider
{
    /**
     * Over-fetch factor: history rows may resolve to records that get filtered
     * out (deleted, no access, excluded), so we read more than requested.
     */
    private const OVERFETCH_FACTOR = 5;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly IconFactory $iconFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {
    }

    /**
     * @return list<RecentEdit>
     */
    public function findRecentEdits(RecentEditsOptions $options): array
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser instanceof BackendUserAuthentication || !is_array($backendUser->user)) {
            return [];
        }

        $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('dashboard');
        $edits = [];

        foreach ($this->fetchHistoryRows($options, (int)$backendUser->user['uid']) as $row) {
            $edit = $this->resolveEdit($row['tablename'], $row['recuid'], $row['latest'], $options, $backendUser, $returnUrl);
            if ($edit !== null) {
                $edits[] = $edit;
            }
            if (count($edits) >= $options->limit) {
                break;
            }
        }

        return $edits;
    }

    /**
     * @return list<array{tablename: string, recuid: int, latest: int}>
     */
    private function fetchHistoryRows(RecentEditsOptions $options, int $userId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_history');
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('tablename', 'recuid')
            ->addSelectLiteral($queryBuilder->expr()->max('tstamp', 'latest'))
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->eq('userid', $queryBuilder->createNamedParameter($userId, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('usertype', $queryBuilder->createNamedParameter('BE')),
                $queryBuilder->expr()->gt('recuid', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
            )
            ->groupBy('tablename', 'recuid')
            ->orderBy('latest', 'DESC')
            ->setMaxResults($options->limit * self::OVERFETCH_FACTOR)
            ->executeQuery();

        $rows = [];
        while ($row = $result->fetchAssociative()) {
            $rows[] = [
                'tablename' => (string)$row['tablename'],
                'recuid' => (int)$row['recuid'],
                'latest' => (int)$row['latest'],
            ];
        }

        return $rows;
    }

    private function resolveEdit(
        string $table,
        int $uid,
        int $timestamp,
        RecentEditsOptions $options,
        BackendUserAuthentication $backendUser,
        string $returnUrl,
    ): ?RecentEdit {
        if (!$this->isTableAllowed($table, $options)) {
            return null;
        }
        if (!$backendUser->check('tables_modify', $table)) {
            return null;
        }

        $record = BackendUtility::getRecord($table, $uid);
        if (!is_array($record)) {
            return null;
        }
        if (!$backendUser->checkRecordEditAccess($table, $record)->isAllowed) {
            return null;
        }

        $pid = isset($record['pid']) ? (int)$record['pid'] : null;

        return new RecentEdit(
            table: $table,
            uid: $uid,
            title: BackendUtility::getRecordTitle($table, $record),
            tableLabel: $this->tableLabel($table),
            iconIdentifier: $this->iconFactory->getIconForRecord($table, $record, IconSize::SMALL)->getIdentifier(),
            editUrl: $this->buildEditUrl($table, $uid, $returnUrl),
            pid: $pid,
            pageTitle: $this->pageTitle($table, $uid, $pid),
            timestamp: $timestamp,
            relativeAge: $this->relativeAge($timestamp),
        );
    }

    private function isTableAllowed(string $table, RecentEditsOptions $options): bool
    {
        if (!isset($GLOBALS['TCA'][$table])) {
            return false;
        }
        if (in_array($table, $options->excludedTables, true)) {
            return false;
        }
        if ($options->allowedTables !== [] && !in_array($table, $options->allowedTables, true)) {
            return false;
        }

        return true;
    }

    private function buildEditUrl(string $table, int $uid, string $returnUrl): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [$table => [$uid => 'edit']],
            'returnUrl' => $returnUrl,
        ]);
    }

    private function pageTitle(string $table, int $uid, ?int $pid): string
    {
        $pageUid = $table === 'pages' ? $uid : $pid;
        if ($pageUid === null || $pageUid <= 0) {
            return '';
        }

        $page = BackendUtility::getRecord('pages', $pageUid, 'uid,title', '', false);

        return is_array($page) ? (string)($page['title'] ?? '') : '';
    }

    private function tableLabel(string $table): string
    {
        $title = (string)($GLOBALS['TCA'][$table]['ctrl']['title'] ?? $table);

        return $this->getLanguageService()->sL($title) ?: $table;
    }

    private function relativeAge(int $timestamp): string
    {
        return trim(BackendUtility::calcAge(
            (int)$GLOBALS['EXEC_TIME'] - $timestamp,
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears'),
        ));
    }

    private function getLanguageService(): LanguageService
    {
        return $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;

        return $backendUser instanceof BackendUserAuthentication ? $backendUser : null;
    }
}
