<?php

declare(strict_types=1);

namespace App\Front\Presentation\View;

use App\Front\Application\Home\Show\Query\ICategoryQuery;
use App\Front\Application\Layout\Query\IPageLinkQuery;
use App\Front\Application\Layout\Query\ISiteSettingQuery;
use Psr\SimpleCache\CacheInterface;

final readonly class LayoutGlobalProvider
{
    private const CACHE_KEY = 'front.layout_globals';

    public function __construct(
        private IPageLinkQuery $pageLinks,
        private ISiteSettingQuery $settings,
        private ICategoryQuery $categories,
        private CacheInterface $cache,
        private int $ttlSeconds,
    ) {
    }

    /** @return array<string, mixed> */
    public function globals(): array
    {
        if ($this->ttlSeconds <= 0) {
            return $this->loadGlobals();
        }

        $cached = $this->cache->get(key: self::CACHE_KEY);
        if (is_array($cached)) {
            /** @var array<string, mixed> $cached */
            return $cached;
        }

        $globals = $this->loadGlobals();
        $this->cache->set(key: self::CACHE_KEY, value: $globals, ttl: $this->ttlSeconds);

        return $globals;
    }

    /** @return array<string, mixed> */
    private function loadGlobals(): array
    {
        $settings = $this->settings->current();

        /** @var array<string, mixed> $globals */
        $globals = [
            'siteName' => $settings->siteName,
            'description' => $settings->siteDescription,
            'frontTheme' => $settings->frontTheme,
            'searchEnabled' => $settings->searchEnabled,
            'heroImage' => $settings->heroImage,
            'publishedPages' => array_map(
                static fn ($page): array => $page->toArray(),
                $this->pageLinks->publishedPageLinks(),
            ),
            'publishedCategories' => array_map(
                static fn ($category): array => $category->toArray(),
                $this->categories->all(),
            ),
        ];

        return $globals;
    }
}
