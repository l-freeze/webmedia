<?php

declare(strict_types=1);

namespace Tests\Shared\Cache;

use App\Shared\Cache\Infrastructure\FileSimpleCache;
use App\Shared\Cache\Infrastructure\InvalidCacheKeyException;
use PHPUnit\Framework\TestCase;

final class FileSimpleCacheTest extends TestCase
{
    public function testStoresAndReadsValue(): void
    {
        $cache = new FileSimpleCache(directory: $this->temporaryDirectory());

        self::assertTrue($cache->set(key: 'front.article_1', value: ['title' => 'Hello'], ttl: 60));

        self::assertTrue($cache->has(key: 'front.article_1'));
        self::assertSame(['title' => 'Hello'], $cache->get(key: 'front.article_1'));
    }

    public function testExpiresValue(): void
    {
        $cache = new FileSimpleCache(directory: $this->temporaryDirectory());

        self::assertTrue($cache->set(key: 'front.article_1', value: 'expired', ttl: -1));

        self::assertFalse($cache->has(key: 'front.article_1'));
        self::assertSame('default', $cache->get(key: 'front.article_1', default: 'default'));
    }

    public function testMultipleOperations(): void
    {
        $cache = new FileSimpleCache(directory: $this->temporaryDirectory());

        self::assertTrue($cache->setMultiple(values: ['a' => 1, 'b' => 2], ttl: 60));
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 0], $cache->getMultiple(keys: ['a', 'b', 'c'], default: 0));

        self::assertTrue($cache->deleteMultiple(keys: ['a', 'b']));
        self::assertSame(['a' => null, 'b' => null], $cache->getMultiple(keys: ['a', 'b']));
    }

    public function testRejectsInvalidKey(): void
    {
        $this->expectException(InvalidCacheKeyException::class);

        (new FileSimpleCache(directory: $this->temporaryDirectory()))->get(key: 'front/article');
    }

    private function temporaryDirectory(): string
    {
        return sys_get_temp_dir() . '/webmedia-cache-' . bin2hex(random_bytes(4));
    }
}
