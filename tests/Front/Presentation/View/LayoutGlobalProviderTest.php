<?php

declare(strict_types=1);

namespace Tests\Front\Presentation\View;

use App\Front\Application\Home\Show\Query\ICategoryQuery;
use App\Front\Application\Home\Show\Value\Category;
use App\Front\Application\Layout\Query\IPageLinkQuery;
use App\Front\Application\Layout\Query\ISiteSettingQuery;
use App\Front\Application\Layout\Value\PageLink;
use App\Front\Application\Layout\Value\SiteSettings;
use App\Front\Presentation\View\LayoutGlobalProvider;
use DateInterval;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class LayoutGlobalProviderTest extends TestCase
{
    public function testGlobalsAreCachedWhenTtlIsPositive(): void
    {
        $pageLinks = new class implements IPageLinkQuery {
            public int $calls = 0;

            public function publishedPageLinks(): array
            {
                $this->calls++;

                return [new PageLink(title: 'About', slug: 'about')];
            }
        };
        $settings = new class implements ISiteSettingQuery {
            public int $calls = 0;

            public function current(): SiteSettings
            {
                $this->calls++;

                return new SiteSettings(
                    siteName: 'WEB Media',
                    siteDescription: 'Description',
                    frontTheme: 'default',
                    searchEnabled: true,
                    heroImage: '/hero.jpg',
                    heroLabel: 'Pick up',
                    heroTitle: 'Custom hero',
                    heroDescription: 'Custom lead',
                );
            }
        };
        $categories = new class implements ICategoryQuery {
            public int $calls = 0;

            public function all(): array
            {
                $this->calls++;

                return [new Category(id: 1, name: 'PHP', slug: 'php', description: null, articleCount: 3)];
            }
        };
        $cache = new ArrayCache();
        $provider = new LayoutGlobalProvider(
            pageLinks: $pageLinks,
            settings: $settings,
            categories: $categories,
            cache: $cache,
            ttlSeconds: 120,
        );

        $first = $provider->globals();
        $second = $provider->globals();

        self::assertSame($first, $second);
        self::assertSame(1, $pageLinks->calls);
        self::assertSame(1, $settings->calls);
        self::assertSame(1, $categories->calls);
        self::assertSame(120, $cache->ttl);
        self::assertSame('WEB Media', $first['siteName']);
        self::assertSame('Pick up', $first['heroLabel']);
        self::assertSame('Custom hero', $first['heroTitle']);
        self::assertSame('Custom lead', $first['heroDescription']);
        self::assertSame([['title' => 'About', 'slug' => 'about']], $first['publishedPages']);
    }

    public function testGlobalsBypassCacheWhenTtlIsZero(): void
    {
        $pageLinks = new class implements IPageLinkQuery {
            public int $calls = 0;

            public function publishedPageLinks(): array
            {
                $this->calls++;

                return [];
            }
        };
        $settings = new class implements ISiteSettingQuery {
            public int $calls = 0;

            public function current(): SiteSettings
            {
                $this->calls++;

                return new SiteSettings(
                    siteName: 'WEB Media',
                    siteDescription: '',
                    frontTheme: 'default',
                    searchEnabled: false,
                );
            }
        };
        $categories = new class implements ICategoryQuery {
            public int $calls = 0;

            public function all(): array
            {
                $this->calls++;

                return [];
            }
        };
        $cache = new ArrayCache();
        $provider = new LayoutGlobalProvider(
            pageLinks: $pageLinks,
            settings: $settings,
            categories: $categories,
            cache: $cache,
            ttlSeconds: 0,
        );

        $provider->globals();
        $provider->globals();

        self::assertSame(2, $pageLinks->calls);
        self::assertSame(2, $settings->calls);
        self::assertSame(2, $categories->calls);
        self::assertSame(0, $cache->setCalls);
    }
}

final class ArrayCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    public int $setCalls = 0;

    public int|null $ttl = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->setCalls++;
        $this->ttl = is_int($ttl) ? $ttl : null;
        $this->values[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get(key: $key, default: $default);
        }

        return $values;
    }

    /** @param iterable<array-key, mixed> $values */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $this->set(key: $key, value: $value, ttl: $ttl);
            }
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete(key: $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}
