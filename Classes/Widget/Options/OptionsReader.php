<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

/**
 * Immutable, path-aware reader for raw widget option arrays.
 *
 * Every accessor knows where it sits in the option tree, so validation errors
 * can be reported with the exact config path (e.g. `components.1.product`).
 */
final readonly class OptionsReader
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data = [],
        private string $path = '',
    ) {
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function int(string $key, int $default): int
    {
        if (!$this->has($key)) {
            return $default;
        }
        $value = $this->data[$key];
        if (!is_int($value) && !(is_string($value) && is_numeric($value))) {
            $this->fail($key, 'must be an integer');
        }

        return (int)$value;
    }

    public function bool(string $key, bool $default): bool
    {
        if (!$this->has($key)) {
            return $default;
        }

        return (bool)$this->data[$key];
    }

    public function string(string $key, ?string $default = null): ?string
    {
        if (!$this->has($key)) {
            return $default;
        }
        $value = $this->data[$key];
        if (!is_string($value) && !is_int($value)) {
            $this->fail($key, 'must be a string');
        }

        return (string)$value;
    }

    public function requireString(string $key): string
    {
        $value = $this->string($key);
        if ($value === null || $value === '') {
            $this->fail($key, 'is required and must not be empty');
        }

        return $value;
    }

    /**
     * @param list<string> $default
     * @return list<string>
     */
    public function stringList(string $key, array $default = []): array
    {
        if (!$this->has($key)) {
            return $default;
        }
        $value = $this->data[$key];
        if (!is_array($value)) {
            $this->fail($key, 'must be a list of strings');
        }

        return array_values(array_map(static fn (mixed $item): string => (string)$item, $value));
    }

    /**
     * @return array<string, string>
     */
    public function stringMap(string $key): array
    {
        if (!$this->has($key)) {
            return [];
        }
        $value = $this->data[$key];
        if (!is_array($value)) {
            $this->fail($key, 'must be a key-value map');
        }
        $map = [];
        foreach ($value as $mapKey => $mapValue) {
            $map[(string)$mapKey] = (string)$mapValue;
        }

        return $map;
    }

    /**
     * Returns a child reader for a nested map, carrying the extended path.
     */
    public function child(string $key): self
    {
        $value = $this->has($key) ? $this->data[$key] : [];
        if (!is_array($value)) {
            $this->fail($key, 'must be a map');
        }

        return new self($value, $this->pathFor($key));
    }

    /**
     * Returns one path-aware reader per entry of a list of maps.
     *
     * @return list<self>
     */
    public function children(string $key): array
    {
        if (!$this->has($key)) {
            return [];
        }
        $value = $this->data[$key];
        if (!is_array($value)) {
            $this->fail($key, 'must be a list');
        }

        $readers = [];
        $index = 0;
        foreach ($value as $entry) {
            if (!is_array($entry)) {
                (new self([], $this->pathFor($key) . '.' . $index))->failSelf('must be a map');
            }
            $readers[] = new self($entry, $this->pathFor($key) . '.' . $index);
            ++$index;
        }

        return $readers;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @param list<string> $keys
     * @return list<string> keys that are actually present
     */
    public function present(array $keys): array
    {
        return array_values(array_filter($keys, fn (string $key): bool => $this->has($key)));
    }

    /**
     * Reports a validation error for this reader's own position.
     */
    public function failSelf(string $message): never
    {
        throw new InvalidWidgetOptionsException(
            ($this->path !== '' ? $this->path . ': ' : '') . $message
        );
    }

    /**
     * Reports a validation error for a child key of this reader.
     */
    public function fail(string $key, string $message): never
    {
        throw new InvalidWidgetOptionsException($this->pathFor($key) . ': ' . $message);
    }

    private function pathFor(string $key): string
    {
        return $this->path === '' ? $key : $this->path . '.' . $key;
    }
}
