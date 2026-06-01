<?php

declare(strict_types=1);

namespace Tests\Shared\Cache;

use App\Shared\Cache\Infrastructure\NullCache;
use PHPUnit\Framework\TestCase;

final class NullCacheTest extends TestCase
{
    public function testDoesNotStoreValues(): void
    {
        $cache = new NullCache();

        self::assertTrue($cache->set(key: 'front.article_1', value: 'value', ttl: 60));
        self::assertFalse($cache->has(key: 'front.article_1'));
        self::assertSame('default', $cache->get(key: 'front.article_1', default: 'default'));
    }
}
