<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Domain\Repository;

use Doctrine\DBAL\ParameterType;

class ContentRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'tt_content';
    }

    public function getFirstContentElementId(int $pageId, int $contentElementId, int $colPos, int $sysLanguageId): int
    {
        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder->select('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, ParameterType::INTEGER))
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter($colPos, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLanguageId, ParameterType::INTEGER)
                )
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        $i = 0;
        foreach ($result as $contentElement) {
            $i++;
            if ((int)$contentElement['uid'] === $contentElementId) {
                return $i;
            }
        }

        return 0;
    }
}
