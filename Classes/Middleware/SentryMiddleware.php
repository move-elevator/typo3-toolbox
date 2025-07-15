<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Middleware;

use JsonException;
use MoveElevator\Typo3Toolbox\Enumeration\Configuration;
use MoveElevator\Typo3Toolbox\Enumeration\Route;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class SentryMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(Configuration::EXT_KEY->value);
        if (!(bool)($extConf['sentryFrontendEnabled'] ?? false)) {
            return $handler->handle($request);
        }

        if ('GET' !== $request->getMethod()) {
            return $handler->handle($request);
        }

        if (Route::API_SENTRY->value !== $request->getUri()->getPath()) {
            return $handler->handle($request);
        }

        $response = $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        $responseData = [
            'dsn' => (string)getenv('SENTRY_DSN'),
            'env' => (string)getenv('SENTRY_ENVIRONMENT'),
            'release' => (string)getenv('SENTRY_RELEASE'),
        ];

        try {
            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            $response->getBody()->write('[]');
        }

        return $response;
    }
}
