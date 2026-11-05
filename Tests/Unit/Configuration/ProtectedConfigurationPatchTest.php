<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProtectedConfigurationPatchTest extends UnitTestCase
{
    private const PLACEHOLDER = '<set by dotenv>';

    /**
     * @return \Generator<string, array{?list<string>, array, array, bool, array}>
     */
    public static function configurations(): \Generator
    {
        yield 'extension synchronization keeps the runtime secret' => [
            ['EXTENSIONS/typo3_image_compression/apiKey'],
            ['EXTENSIONS' => ['typo3_image_compression' => ['apiKey' => 'dummy-api-secret', 'enabled' => '1']]],
            ['EXTENSIONS' => ['typo3_image_compression' => ['apiKey' => self::PLACEHOLDER, 'enabled' => '1']]],
            true,
            [],
        ];
        yield 'direct writer protects multiple nested paths' => [
            ['DB/Connections/Default/password', 'MAIL/transport_smtp_password'],
            ['DB' => ['Connections' => ['Default' => ['password' => 'dummy-db-secret', 'host' => 'localhost']]], 'MAIL' => ['transport_smtp_password' => 'dummy-mail-secret']],
            ['DB' => ['Connections' => ['Default' => ['host' => 'localhost', 'password' => self::PLACEHOLDER]]], 'MAIL' => ['transport_smtp_password' => self::PLACEHOLDER]],
            false,
            [],
        ];
        yield 'missing paths are not created' => [
            ['EXTENSIONS/missing/apiKey'],
            ['SYS' => ['sitename' => 'Example']],
            ['SYS' => ['sitename' => 'Example']],
            false,
            [],
        ];
        yield 'null values at protected paths become the placeholder' => [
            ['EXTENSIONS/example/apiKey'],
            ['EXTENSIONS' => ['example' => ['apiKey' => null]]],
            ['EXTENSIONS' => ['example' => ['apiKey' => self::PLACEHOLDER]]],
            false,
            [],
        ];
        yield 'no configuration leaves values untouched' => [
            null,
            ['EXTENSIONS' => ['example' => ['apiKey' => 'dummy-unprotected-value']]],
            ['EXTENSIONS' => ['example' => ['apiKey' => 'dummy-unprotected-value']]],
            false,
            [],
        ];
        yield 'an empty path list leaves values untouched' => [
            [],
            ['SYS' => ['sitename' => 'Example']],
            ['SYS' => ['sitename' => 'Example']],
            false,
            [],
        ];
        yield 'placeholders already in settings.php are protected without a list entry' => [
            null,
            ['EXTENSIONS' => ['typo3_image_compression' => ['apiKey' => 'dummy-api-secret', 'enabled' => '1']]],
            ['EXTENSIONS' => ['typo3_image_compression' => ['apiKey' => self::PLACEHOLDER, 'enabled' => '1']]],
            true,
            ['EXTENSIONS' => ['typo3_image_compression' => ['apiKey' => self::PLACEHOLDER, 'enabled' => '0']]],
        ];
        yield 'a previously leaked value at a listed path is replaced' => [
            ['MAIL/transport_smtp_password'],
            ['MAIL' => ['transport_smtp_password' => 'dummy-mail-secret']],
            ['MAIL' => ['transport_smtp_password' => self::PLACEHOLDER]],
            false,
            ['MAIL' => ['transport_smtp_password' => 'dummy-mail-secret']],
        ];
        yield 'a removed placeholder path is not recreated' => [
            null,
            ['SYS' => ['sitename' => 'Example']],
            ['SYS' => ['sitename' => 'Example']],
            false,
            ['EXTENSIONS' => ['example' => ['apiKey' => self::PLACEHOLDER]], 'SYS' => ['sitename' => 'Example']],
        ];
        yield 'a migrated path is protected when it is listed' => [
            ['MAIL/transport_smtp/password'],
            ['MAIL' => ['transport_smtp' => ['password' => 'dummy-mail-secret']]],
            ['MAIL' => ['transport_smtp' => ['password' => self::PLACEHOLDER]]],
            false,
            ['MAIL' => ['transport_smtp_password' => self::PLACEHOLDER]],
        ];
        yield 'a placeholder under a key containing the path delimiter stays protected' => [
            null,
            ['EXTENSIONS' => ['example' => ['service/main' => ['apiKey' => 'dummy-api-secret']]]],
            ['EXTENSIONS' => ['example' => ['service/main' => ['apiKey' => self::PLACEHOLDER]]]],
            false,
            ['EXTENSIONS' => ['example' => ['service/main' => ['apiKey' => self::PLACEHOLDER]]]],
        ];
        yield 'a listed path pointing to a section is left untouched' => [
            ['DB/Connections/Default'],
            ['DB' => ['Connections' => ['Default' => ['host' => 'localhost']]]],
            ['DB' => ['Connections' => ['Default' => ['host' => 'localhost']]]],
            false,
            [],
        ];
    }

    #[Test]
    #[DataProvider('configurations')]
    public function patchFiltersTheFileWithoutChangingRuntimeConfiguration(
        ?array $protectedPaths,
        array $configuration,
        array $expected,
        bool $extensionWrite,
        array $initialSettings,
    ): void {
        $result = $this->writeConfiguration($protectedPaths, $configuration, $extensionWrite, $initialSettings);

        self::assertSame($expected, $result['stored']);
        self::assertTrue($result['runtimePreserved']);
        foreach (['dummy-api-secret', 'dummy-db-secret', 'dummy-mail-secret'] as $secret) {
            self::assertStringNotContainsString($secret, $result['contents']);
        }
    }

    /**
     * @return \Generator<string, array{mixed}>
     */
    public static function invalidPaths(): \Generator
    {
        yield 'single path supplied as string' => ['EXTENSIONS/example/apiKey'];
        yield 'empty path' => [['']];
        yield 'array path instead of string' => [[['EXTENSIONS', 'example', 'apiKey']]];
    }

    #[Test]
    #[DataProvider('invalidPaths')]
    public function malformedProtectionConfigurationAbortsBeforeWriting(mixed $protectedPaths): void
    {
        $result = $this->writeConfiguration(
            $protectedPaths,
            ['EXTENSIONS' => ['example' => ['apiKey' => 'dummy-api-secret']]],
        );

        self::assertTrue($result['writeRejected'] ?? false);
        self::assertTrue($result['fileUnchanged'] ?? false);
        self::assertTrue($result['runtimePreserved']);
    }

    private function writeConfiguration(
        mixed $protectedPaths,
        array $configuration,
        bool $extensionWrite = false,
        array $initialSettings = [],
    ): array {
        $projectRoot = dirname(__DIR__, 3);
        $temporaryRoot = Environment::getVarPath() . '/tests/protected-configuration-' . bin2hex(random_bytes(8));
        GeneralUtility::mkdir_deep($temporaryRoot . '/Classes/Configuration');
        $this->testFilesToDelete[] = $temporaryRoot;
        copy(
            $projectRoot . '/vendor/typo3/cms-core/Classes/Configuration/ConfigurationManager.php',
            $temporaryRoot . '/Classes/Configuration/ConfigurationManager.php',
        );

        $patch = $projectRoot . '/patches/typo3-cms-core-protected-configuration.patch';
        self::assertFileExists($patch);
        $this->runCommand([
            'git',
            '--git-dir=' . $temporaryRoot . '/.git',
            '--work-tree=' . $temporaryRoot,
            'apply',
            '--no-index',
            $patch,
        ], $temporaryRoot);
        $output = $this->runCommand([
            PHP_BINARY,
            '-d',
            'display_errors=stderr',
            $projectRoot . '/Tests/Fixtures/Configuration/write-settings.php',
            $projectRoot . '/vendor',
            $temporaryRoot,
            json_encode(compact('protectedPaths', 'configuration', 'extensionWrite', 'initialSettings'), JSON_THROW_ON_ERROR),
        ], $temporaryRoot);
        return json_decode($output, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<string> $command
     */
    private function runCommand(array $command, string $directory): string
    {
        // stderr goes to a file so a full stderr pipe cannot block the child while stdout is read
        $errorFile = $directory . '/stderr.log';
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['file', $errorFile, 'w']], $pipes, $directory);
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        self::assertSame(0, proc_close($process), (string)file_get_contents($errorFile));

        return $output;
    }
}
