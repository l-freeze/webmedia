<?php

declare(strict_types=1);

namespace App\Shared\Cache\Infrastructure;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

final readonly class FileSimpleCache implements CacheInterface
{
    private const MAX_KEY_LENGTH = 64;
    private const HASH_DIRECTORY_PREFIX_LENGTH = 2;
    private const DIRECTORY_PERMISSIONS = 0775;

    public function __construct(
        private string $directory,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey(key: $key);
        $record = $this->read(key: $key);
        if ($record === null) {
            return $default;
        }

        if ($this->isExpired(expiresAt: $record['expiresAt'])) {
            $this->delete(key: $key);

            return $default;
        }

        return $record['value'];
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->assertValidKey(key: $key);
        $expiresAt = $this->expiresAt(ttl: $ttl);
        if ($expiresAt !== null && $expiresAt <= time()) {
            return $this->delete(key: $key);
        }

        $this->ensureKeyDirectory(key: $key);

        return file_put_contents(
            filename: $this->path(key: $key),
            data: serialize(['expiresAt' => $expiresAt, 'value' => $value]),
            flags: LOCK_EX,
        ) !== false;
    }

    public function delete(string $key): bool
    {
        $this->assertValidKey(key: $key);
        $path = $this->path(key: $key);

        return !is_file($path) || unlink($path);
    }

    public function clear(): bool
    {
        if (!is_dir($this->directory)) {
            return true;
        }

        $ok = true;
        $iterator = new RecursiveIteratorIterator(
            iterator: new RecursiveDirectoryIterator($this->directory, RecursiveDirectoryIterator::SKIP_DOTS),
            mode: RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            $path = $file->getPathname();
            $ok = $file->isDir() ? rmdir($path) && $ok : unlink($path) && $ok;
        }

        return $ok;
    }

    /** @param iterable<mixed> $keys */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('Cache key must be a string.');
            }

            $values[$key] = $this->get(key: $key, default: $default);
        }

        return $values;
    }

    /** @param iterable<array-key, mixed> $values */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $ok = true;
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('Cache key must be a string.');
            }

            $ok = $this->set(key: $key, value: $value, ttl: $ttl) && $ok;
        }

        return $ok;
    }

    /** @param iterable<mixed> $keys */
    public function deleteMultiple(iterable $keys): bool
    {
        $ok = true;
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('Cache key must be a string.');
            }

            $ok = $this->delete(key: $key) && $ok;
        }

        return $ok;
    }

    public function has(string $key): bool
    {
        $this->assertValidKey(key: $key);

        $record = $this->read(key: $key);
        if ($record === null) {
            return false;
        }

        if ($this->isExpired(expiresAt: $record['expiresAt'])) {
            $this->delete(key: $key);

            return false;
        }

        return true;
    }

    private function assertValidKey(string $key): void
    {
        if ($key === '' || strlen($key) > self::MAX_KEY_LENGTH || preg_match('/^[A-Za-z0-9_.]+$/', $key) !== 1) {
            throw new InvalidCacheKeyException("Invalid cache key: {$key}");
        }
    }

    private function path(string $key): string
    {
        $hash = hash('sha256', $key);

        return $this->directory . DIRECTORY_SEPARATOR . substr($hash, 0, self::HASH_DIRECTORY_PREFIX_LENGTH) . DIRECTORY_SEPARATOR . $hash . '.cache';
    }

    private function ensureKeyDirectory(string $key): void
    {
        $directory = dirname($this->path(key: $key));
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, self::DIRECTORY_PERMISSIONS, true) && !is_dir($directory)) {
            throw new RuntimeException("Failed to create cache directory: {$directory}");
        }
    }

    /**
     * @return array{expiresAt:int|null,value:mixed}|null
     */
    private function read(string $key): ?array
    {
        $path = $this->path(key: $key);
        if (!is_file($path)) {
            return null;
        }

        $payload = file_get_contents($path);
        if ($payload === false) {
            return null;
        }

        try {
            $record = unserialize($payload, ['allowed_classes' => true]);
        } catch (Throwable) {
            return null;
        }

        if (!is_array($record) || !array_key_exists('expiresAt', $record) || !array_key_exists('value', $record)) {
            return null;
        }

        $expiresAt = $record['expiresAt'];
        if ($expiresAt !== null && !is_int($expiresAt)) {
            return null;
        }

        return ['expiresAt' => $expiresAt, 'value' => $record['value']];
    }

    private function expiresAt(DateInterval|int|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return time() + $ttl;
        }

        return (new DateTimeImmutable())->add($ttl)->getTimestamp();
    }

    private function isExpired(?int $expiresAt): bool
    {
        return $expiresAt !== null && $expiresAt <= time();
    }
}
