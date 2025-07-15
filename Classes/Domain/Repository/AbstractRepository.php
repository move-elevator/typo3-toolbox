<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Domain\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractRepository
{
    abstract protected function getTableName(): string;

    public function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->getTableName());
    }

    public function getQueryBuilderForChildTable(string $tableName): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
    }

    public function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->getTableName());
    }

    public function getConnectionForChildTable(string $tableName): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
    }

    /**
    * @param string[] $columns
    * @return mixed[]
    */
    public function findAll(array $columns = ['*'], string $orderBy = 'uid', int $limit = 0): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select(...$columns)
            ->from($this->getTableName())
            ->orderBy($orderBy);

        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
    * @param string[] $columns
    * @return mixed[]
    */
    public function findById(int $id, array $columns = ['*']): array
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder
            ->select(...$columns)
            ->from($this->getTableName())
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchAssociative();
    }

    public function deleteById(int $id): void
    {
        $this->getConnection()->delete($this->getTableName(), ['uid' => $id]);
    }

    /**
    * @param mixed[] $values
    */
    public function insert(array $values): int
    {
        return $this->getQueryBuilder()->insert($this->getTableName())->values($values)->executeStatement();
    }
}
