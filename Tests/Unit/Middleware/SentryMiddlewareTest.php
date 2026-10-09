<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Middleware;

use MoveElevator\Typo3Toolbox\Middleware\SentryMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SentryMiddlewareTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public static function handledPathsDataProvider(): \Generator
    {
        yield 'document root' => ['https://example.com/api/sentry'];
        yield 'feature branch subpath' => ['https://example.com/feature-typo3-14/api/sentry'];
        yield 'relative to a nested document' => ['https://example.com/feature-typo3-14/some/deep/page/api/sentry'];
    }

    public static function passedThroughPathsDataProvider(): \Generator
    {
        yield 'unrelated page' => ['https://example.com/some/page'];
        yield 'route is not on a segment boundary' => ['https://example.com/my-api/sentry'];
        yield 'route is not the last segment' => ['https://example.com/api/sentry/extra'];
    }

    #[Test]
    #[DataProvider('handledPathsDataProvider')]
    public function routeIsAnsweredRegardlessOfTheSiteBasePath(string $uri): void
    {
        $this->mockFrontendEnabled();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = (new SentryMiddleware(new ResponseFactory()))
            ->process(new ServerRequest($uri, 'GET'), $handler)
        ;

        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame(['dsn', 'env', 'release'], array_keys(
            json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR)
        ));
    }

    #[Test]
    #[DataProvider('passedThroughPathsDataProvider')]
    public function otherRoutesArePassedToTheNextHandler(string $uri): void
    {
        $this->mockFrontendEnabled();

        $handled = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn($handled);

        $response = (new SentryMiddleware(new ResponseFactory()))
            ->process(new ServerRequest($uri, 'GET'), $handler)
        ;

        self::assertSame($handled, $response);
    }

    #[Test]
    public function nonGetRequestsArePassedToTheNextHandler(): void
    {
        $this->mockFrontendEnabled();

        $handled = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn($handled);

        $response = (new SentryMiddleware(new ResponseFactory()))
            ->process(new ServerRequest('https://example.com/feature-typo3-14/api/sentry', 'POST'), $handler)
        ;

        self::assertSame($handled, $response);
    }

    private function mockFrontendEnabled(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn(['sentryFrontendEnabled' => '1']);

        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration);
    }
}
