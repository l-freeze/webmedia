<?php

declare(strict_types=1);

namespace App\Shared\Cache\Infrastructure;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final readonly class NullCache implements CacheInterface
{
    private const MAX_KEY_LENGTH = 64;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey(key: $key);

        return $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->assertValidKey(key: $key);

        return true;
    }

    public function delete(string $key): bool
    {
        $this->assertValidKey(key: $key);

        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    /** @param iterable<mixed> $keys */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('Cache key must be a string.');
            }

            $this->assertValidKey(key: $key);
            $values[$key] = $default;
        }

        return $values;
    }

    /** @param iterable<array-key, mixed> $values */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('Cache key must be a string.');
            }

            $this->assertValidKey(key: $key);
        }

        return true;
    }

    /** @param iterable<mixed> $keys */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('Cache key must be a string.');
            }

            $this->assertValidKey(key: $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->assertValidKey(key: $key);

        return false;
    }

    private function assertValidKey(string $key): void
    {
        if ($key === '' || strlen($key) > self::MAX_KEY_LENGTH || preg_match('/^[A-Za-z0-9_.]+$/', $key) !== 1) {
            throw new InvalidCacheKeyException("Invalid cache key: {$key}");
        }
    }
}
