<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Widget\EndOfLife;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDB110700Platform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use MoveElevator\Typo3Toolbox\Widget\EndOfLife\DatabasePlatformDetector;
use TYPO3\CMS\Core\Database\ConnectionPool;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabasePlatformDetectorTest extends UnitTestCase
{
    private static function detector(): DatabasePlatformDetector
    {
        return new DatabasePlatformDetector(new ConnectionPool());
    }

    /**
     * @return \Generator<string, array{AbstractPlatform, string, string, string, string}>
     */
    public static function supportedPlatforms(): \Generator
    {
        yield 'MariaDB reports a suffixed version' => [
            new MariaDBPlatform(),
            '11.4.8-MariaDB-ubu2404',
            'mariadb',
            '11.4',
            'MariaDB',
        ];
        yield 'MariaDB version-specific platform still maps to mariadb' => [
            new MariaDB110700Platform(),
            '11.7.2-MariaDB',
            'mariadb',
            '11.7',
            'MariaDB',
        ];
        yield 'MySQL is not mistaken for MariaDB' => [
            new MySQLPlatform(),
            '8.0.36',
            'mysql',
            '8.0',
            'MySQL',
        ];
        yield 'MySQL version-specific platform still maps to mysql' => [
            new MySQL84Platform(),
            '8.4.3',
            'mysql',
            '8.4',
            'MySQL',
        ];
        yield 'PostgreSQL cycles are major only' => [
            new PostgreSQLPlatform(),
            '16.2 (Debian 16.2-1.pgdg120+2)',
            'postgresql',
            '16',
            'PostgreSQL',
        ];
    }

    #[Test]
    #[DataProvider('supportedPlatforms')]
    public function resolvesPlatformToEndoflifeProduct(
        AbstractPlatform $platform,
        string $serverVersion,
        string $expectedProduct,
        string $expectedVersion,
        string $expectedLabel,
    ): void {
        $request = self::detector()->resolve($platform, $serverVersion);

        self::assertNotNull($request);
        self::assertSame($expectedProduct, $request->product);
        self::assertSame($expectedVersion, $request->version);
        self::assertSame($expectedLabel, $request->label());
        self::assertFalse($request->eltsContract);
    }

    /**
     * SQLite has no meaningful support lifecycle to warn about, so it is skipped
     * rather than drawn as an endless bar.
     */
    #[Test]
    public function unsupportedPlatformIsSkipped(): void
    {
        self::assertNull(self::detector()->resolve(new SQLitePlatform(), '3.45.1'));
    }

    #[Test]
    public function unparseableServerVersionIsSkipped(): void
    {
        self::assertNull(self::detector()->resolve(new MariaDBPlatform(), 'unknown'));
    }
}
