<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\EndOfLife;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use MoveElevator\Typo3Toolbox\Widget\Options\ComponentRequest;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Derives the endoflife.date component of the default database connection.
 *
 * Only platforms with a support lifecycle worth warning about are mapped;
 * SQLite and anything unrecognized is skipped rather than drawn as an endless
 * bar. Version granularity follows the product's release cycles: MariaDB and
 * MySQL are tracked per `major.minor`, PostgreSQL per `major`.
 */
final readonly class DatabasePlatformDetector
{
    /**
     * @var array<string, array{product: string, label: string, minorCycles: bool}>
     */
    private const array PLATFORMS = [
        MariaDBPlatform::class => ['product' => 'mariadb', 'label' => 'MariaDB', 'minorCycles' => true],
        AbstractMySQLPlatform::class => ['product' => 'mysql', 'label' => 'MySQL', 'minorCycles' => true],
        PostgreSQLPlatform::class => ['product' => 'postgresql', 'label' => 'PostgreSQL', 'minorCycles' => false],
    ];

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {
    }

    /**
     * Never throws: a database that cannot be reached or identified simply
     * contributes no component, so the rest of the widget still renders.
     */
    public function detect(): ?ComponentRequest
    {
        try {
            $connection = $this->connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

            return $this->resolve($connection->getDatabasePlatform(), $connection->getServerVersion());
        } catch (\Throwable) {
            return null;
        }
    }

    public function resolve(AbstractPlatform $platform, string $serverVersion): ?ComponentRequest
    {
        // MariaDB extends the MySQL platform, so it has to be matched first.
        foreach (self::PLATFORMS as $class => $product) {
            if (!$platform instanceof $class) {
                continue;
            }

            $version = $this->cycle($serverVersion, $product['minorCycles']);

            return $version === null ? null : new ComponentRequest(
                product: $product['product'],
                version: $version,
                eltsContract: false,
                label: $product['label'],
            );
        }

        return null;
    }

    /**
     * Reduces a server version string — which may carry vendor and distribution
     * suffixes such as `11.4.8-MariaDB-ubu2404` — to its release cycle.
     */
    private function cycle(string $serverVersion, bool $minorCycles): ?string
    {
        $version = ltrim($serverVersion);

        if ($minorCycles) {
            return preg_match('/^(\d+)\.(\d+)/', $version, $matches) === 1
                ? $matches[1] . '.' . $matches[2]
                : null;
        }

        return preg_match('/^(\d+)/', $version, $matches) === 1 ? $matches[1] : null;
    }
}
