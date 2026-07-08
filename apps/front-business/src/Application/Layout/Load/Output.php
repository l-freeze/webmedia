<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Layout\Load;

use App\FrontBusiness\Application\Layout\Load\Value\Category;
use App\FrontBusiness\Application\Layout\Load\Value\PageLink;
use App\FrontBusiness\Application\Layout\Load\Value\SiteSettings;

final readonly class Output
{
    /**
     * @param array<int, PageLink> $publishedPages
     * @param array<int, Category> $publishedCategories
     */
    public function __construct(
        public SiteSettings $settings,
        public array $publishedPages,
        public array $publishedCategories,
    ) {
    }
}
