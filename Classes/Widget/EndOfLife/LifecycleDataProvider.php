<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\EndOfLife;

use MoveElevator\Typo3Toolbox\Enumeration\Configuration;
use MoveElevator\Typo3Toolbox\Widget\Options\ComponentRequest;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Fetches component lifecycle data from the endoflife.date v1 API and turns it
 * into an ordered list of {@see LifecyclePhase}s.
 *
 * Responses are cached for 24h; a stale copy is kept indefinitely and used as a
 * fallback whenever the API is unreachable, so outages degrade gracefully and
 * never break the dashboard.
 *
 * The v1 API models each boundary as a status flag plus an optional date
 * (`isEoas`/`eoasFrom`, `isEol`/`eolFrom`, `isEoes`/`eoesFrom`). A flag that is
 * true without a date means the boundary was already reached at an unknown time
 * — represented here as a distant past marker so such a phase is never drawn as
 * still ongoing.
 */
final readonly class LifecycleDataProvider
{
    private const string API_URL = 'https://endoflife.date/api/v1/products/%s';
    private const int FRESH_SECONDS = 86400;
    private const string CACHE_PREFIX = 'endoflife_';

    public function __construct(
        private RequestFactory $requestFactory,
        private CacheManager $cacheManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Resolves a component request to its lifecycle, or null if the product or
     * version is unknown to endoflife.date (the component is then skipped).
     */
    public function resolve(ComponentRequest $request, \DateTimeImmutable $now): ?ComponentLifecycle
    {
        $releases = $this->fetchReleases($request->product, $now);
        if ($releases === null) {
            return null;
        }

        $release = $this->matchRelease($releases, $request->version);
        if ($release === null) {
            return null;
        }

        return new ComponentLifecycle(
            product: $request->product,
            version: $request->version,
            label: $request->label(),
            phases: $this->buildPhases($release),
            eltsContract: $request->eltsContract,
            securityEnd: $this->parseDate($release['eolFrom'] ?? null),
            eltsEnd: $this->parseDate($release['eoesFrom'] ?? null),
            endOfLifeReached: ($release['isEol'] ?? false) === true,
        );
    }

    /**
     * @param array<string, mixed> $release
     * @return list<LifecyclePhase>
     */
    private function buildPhases(array $release): array
    {
        $start = $this->parseDate($release['releaseDate'] ?? null);
        $activeEnd = $this->boundary($release, 'eoasFrom', 'isEoas');
        $securityEnd = $this->boundary($release, 'eolFrom', 'isEol');
        $eltsEnd = $this->boundary($release, 'eoesFrom', 'isEoes');

        $phases = [];
        $phases[] = new LifecyclePhase(LifecyclePhaseType::FullSupport, $start, $activeEnd ?? $securityEnd ?? $eltsEnd);

        if ($activeEnd !== null && ($securityEnd === null || $securityEnd > $activeEnd)) {
            $phases[] = new LifecyclePhase(LifecyclePhaseType::Security, $activeEnd, $securityEnd);
        }

        if ($eltsEnd !== null) {
            $phases[] = new LifecyclePhase(LifecyclePhaseType::Elts, $securityEnd ?? $activeEnd ?? $start, $eltsEnd);
        }

        return $phases;
    }

    /**
     * Resolves a v1 boundary to a date: the concrete date when known, a distant
     * past marker when the boundary was reached but its date is unknown, or null
     * when the boundary lies open in the future.
     *
     * @param array<string, mixed> $release
     */
    private function boundary(array $release, string $dateKey, string $flagKey): ?\DateTimeImmutable
    {
        $date = $this->parseDate($release[$dateKey] ?? null);
        if ($date !== null) {
            return $date;
        }

        return ($release[$flagKey] ?? false) === true ? new \DateTimeImmutable('@0') : null;
    }

    /**
     * Matches a version to its release: exact first, then version-under-cycle
     * (e.g. "14.3" → "14"), then cycle-under-version (e.g. "8" → "8.4").
     *
     * @param list<array<string, mixed>> $releases
     * @return array<string, mixed>|null
     */
    private function matchRelease(array $releases, string $version): ?array
    {
        return $this->firstReleaseMatching($releases, static fn (string $name): bool => $name === $version)
            ?? $this->firstReleaseMatching($releases, static fn (string $name): bool => $name !== '' && str_starts_with($version . '.', $name . '.'))
            ?? $this->firstReleaseMatching($releases, static fn (string $name): bool => $name !== '' && str_starts_with($name . '.', $version . '.'));
    }

    /**
     * @param list<array<string, mixed>> $releases
     * @param callable(string): bool $predicate
     * @return array<string, mixed>|null
     */
    private function firstReleaseMatching(array $releases, callable $predicate): ?array
    {
        foreach ($releases as $release) {
            if ($predicate((string)($release['name'] ?? ''))) {
                return $release;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function fetchReleases(string $product, \DateTimeImmutable $now): ?array
    {
        $identifier = self::CACHE_PREFIX . (string)preg_replace('/[^a-z0-9]/', '_', strtolower($product));
        $timestamp = $now->getTimestamp();

        [$cachedReleases, $fetchedAt] = $this->readCache($identifier);

        if ($cachedReleases !== null && ($timestamp - $fetchedAt) < self::FRESH_SECONDS) {
            return $cachedReleases;
        }

        $fresh = $this->requestReleases($product);
        if ($fresh !== null) {
            $this->writeCache($identifier, $timestamp, $fresh);

            return $fresh;
        }

        // API unreachable: fall back to a stale copy if we ever cached one.
        return $cachedReleases;
    }

    /**
     * @return array{0: list<array<string, mixed>>|null, 1: int}
     */
    private function readCache(string $identifier): array
    {
        try {
            $entry = $this->cacheManager->getCache(Configuration::EXT_KEY->value)->get($identifier);
        } catch (\Throwable $exception) {
            $this->logger->warning('Could not read lifecycle cache entry "{identifier}": {message}', [
                'identifier' => $identifier,
                'message' => $exception->getMessage(),
            ]);

            return [null, 0];
        }

        return [
            is_array($entry) ? $this->normalizeReleases($entry['releases'] ?? null) : null,
            is_array($entry) ? (int)($entry['fetchedAt'] ?? 0) : 0,
        ];
    }

    /**
     * @param list<array<string, mixed>> $releases
     */
    private function writeCache(string $identifier, int $fetchedAt, array $releases): void
    {
        try {
            $this->cacheManager->getCache(Configuration::EXT_KEY->value)
                ->set($identifier, ['fetchedAt' => $fetchedAt, 'releases' => $releases], [], 0);
        } catch (\Throwable $exception) {
            $this->logger->warning('Could not write lifecycle cache entry "{identifier}": {message}', [
                'identifier' => $identifier,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function requestReleases(string $product): ?array
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

            $data = json_decode((string)$response->getBody(), true);
            $result = is_array($data) ? ($data['result'] ?? null) : null;

            return $this->normalizeReleases(is_array($result) ? ($result['releases'] ?? null) : null);
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
    private function normalizeReleases(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $releases = [];
        foreach ($value as $entry) {
            if (is_array($entry)) {
                $releases[] = $entry;
            }
        }

        return $releases;
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value ? $date : null;
    }
}
