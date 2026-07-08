<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\View;

use App\FrontBusiness\Application\Layout\Load\Input;
use App\FrontBusiness\Application\Layout\Load\Interactor;
use App\FrontBusiness\Presentation\BasePath;
use Psr\SimpleCache\CacheInterface;

final readonly class LayoutGlobalProvider
{
    private const CACHE_KEY = 'front_business.layout_globals.v1';

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
        $output = $this->layout->execute(new Input());
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
            'basePath' => BasePath::value(),
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
