<?php

declare(strict_types=1);

namespace App\Front\Presentation\View;

use App\Front\Application\Layout\Load\Input;
use App\Front\Application\Layout\Load\Interactor;
use Psr\SimpleCache\CacheInterface;

final readonly class LayoutGlobalProvider
{
    private const CACHE_KEY = 'front.layout_globals.v2';

    public function __construct(
        private Interactor $layout,
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
        $output = $this->layout->execute(Input::create());
        $settings = $output->settings;

        /** @var array<string, mixed> $globals */
        $globals = [
            'siteName' => $settings->siteName,
            'description' => $settings->siteDescription,
            'frontTheme' => $settings->frontTheme,
            'searchEnabled' => $settings->searchEnabled,
            'heroImage' => $settings->heroImage,
            'heroTitle' => $settings->heroTitle,
            'heroDescription' => $settings->heroDescription,
            'publishedPages' => array_map(
                static fn ($page): array => $page->toArray(),
                $output->publishedPages,
            ),
            'publishedCategories' => array_map(
                static fn ($category): array => $category->toArray(),
                $output->publishedCategories,
            ),
        ];

        return $globals;
    }
}
