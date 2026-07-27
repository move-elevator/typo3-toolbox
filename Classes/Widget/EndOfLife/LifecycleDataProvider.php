<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\EndOfLife;

use MoveElevator\Typo3Toolbox\Enumeration\Configuration;
use MoveElevator\Typo3Toolbox\Widget\Options\ComponentRequest;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Fetches component lifecycle data from endoflife.date and turns it into an
 * ordered list of {@see LifecyclePhase}s.
 *
 * Responses are cached for 24h; a stale copy is kept indefinitely and used as a
 * fallback whenever the API is unreachable, so outages degrade gracefully and
 * never break the dashboard.
 */
final class LifecycleDataProvider
{
    private const API_URL = 'https://endoflife.date/api/%s.json';
    private const FRESH_SECONDS = 86400;
    private const CACHE_PREFIX = 'endoflife_';

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly CacheManager $cacheManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Resolves a component request to its lifecycle, or null if the product or
     * version is unknown to endoflife.date (the component is then skipped).
     */
    public function resolve(ComponentRequest $request): ?ComponentLifecycle
    {
        $cycles = $this->fetchCycles($request->product);
        if ($cycles === null) {
            return null;
        }

        $cycle = $this->matchCycle($cycles, $request->version);
        if ($cycle === null) {
            return null;
        }

        $securityEnd = $this->parseDate($cycle['eol'] ?? null);
        $eltsEnd = $this->parseDate($cycle['extendedSupport'] ?? null);

        return new ComponentLifecycle(
            product: $request->product,
            version: $request->version,
            label: $request->label(),
            phases: $this->buildPhases($cycle, $securityEnd, $eltsEnd),
            eltsContract: $request->eltsContract,
            securityEnd: $securityEnd,
            eltsEnd: $eltsEnd,
        );
    }

    /**
     * @param array<string, mixed> $cycle
     * @return list<LifecyclePhase>
     */
    private function buildPhases(array $cycle, ?\DateTimeImmutable $securityEnd, ?\DateTimeImmutable $eltsEnd): array
    {
        $release = $this->parseDate($cycle['releaseDate'] ?? null);
        $support = $this->parseDate($cycle['support'] ?? null);

        $phases = [];
        $phases[] = new LifecyclePhase(LifecyclePhaseType::FullSupport, $release, $support ?? $securityEnd);

        if ($support !== null && ($securityEnd === null || $securityEnd > $support)) {
            $phases[] = new LifecyclePhase(LifecyclePhaseType::Security, $support, $securityEnd);
        }

        if ($eltsEnd !== null) {
            $phases[] = new LifecyclePhase(LifecyclePhaseType::Elts, $securityEnd ?? $support ?? $release, $eltsEnd);
        }

        return $phases;
    }

    /**
     * Matches a version to its cycle: exact first, then version-under-cycle
     * (e.g. "14.3" → "14"), then cycle-under-version (e.g. "8" → "8.4").
     *
     * @param list<array<string, mixed>> $cycles
     * @return array<string, mixed>|null
     */
    private function matchCycle(array $cycles, string $version): ?array
    {
        return $this->firstCycleMatching($cycles, static fn (string $name): bool => $name === $version)
            ?? $this->firstCycleMatching($cycles, static fn (string $name): bool => $name !== '' && str_starts_with($version . '.', $name . '.'))
            ?? $this->firstCycleMatching($cycles, static fn (string $name): bool => $name !== '' && str_starts_with($name . '.', $version . '.'));
    }

    /**
     * @param list<array<string, mixed>> $cycles
     * @param callable(string): bool $predicate
     * @return array<string, mixed>|null
     */
    private function firstCycleMatching(array $cycles, callable $predicate): ?array
    {
        foreach ($cycles as $cycle) {
            if ($predicate((string)($cycle['cycle'] ?? ''))) {
                return $cycle;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function fetchCycles(string $product): ?array
    {
        $cache = $this->cacheManager->getCache(Configuration::EXT_KEY->value);
        $identifier = self::CACHE_PREFIX . (string)preg_replace('/[^a-z0-9]/', '_', strtolower($product));
        $now = (int)($GLOBALS['EXEC_TIME'] ?? time());

        $entry = $cache->get($identifier);
        $cachedCycles = is_array($entry) ? $this->normalizeCycles($entry['cycles'] ?? null) : null;
        $fetchedAt = is_array($entry) ? (int)($entry['fetchedAt'] ?? 0) : 0;

        if ($cachedCycles !== null && ($now - $fetchedAt) < self::FRESH_SECONDS) {
            return $cachedCycles;
        }

        $fresh = $this->requestCycles($product);
        if ($fresh !== null) {
            $cache->set($identifier, ['fetchedAt' => $now, 'cycles' => $fresh], [], 0);

            return $fresh;
        }

        // API unreachable: fall back to a stale copy if we ever cached one.
        return $cachedCycles;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function requestCycles(string $product): ?array
    {
        try {
            $response = $this->requestFactory->request(
                sprintf(self::API_URL, rawurlencode($product)),
                'GET',
                ['headers' => ['Accept' => 'application/json'], 'timeout' => 5],
            );
            if ($response->getStatusCode() !== 200) {
                return null;
            }

            return $this->normalizeCycles(json_decode((string)$response->getBody(), true));
        } catch (\Throwable $exception) {
            $this->logger->warning('Could not fetch lifecycle data for "{product}": {message}', [
                'product' => $product,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function normalizeCycles(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $cycles = [];
        foreach ($value as $entry) {
            if (is_array($entry)) {
                $cycles[] = $entry;
            }
        }

        return $cycles;
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }
}
