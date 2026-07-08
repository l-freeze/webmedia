<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Sitemap\Build;

use App\FrontBusiness\Application\Sitemap\Build\Value\SitemapEntry;

final readonly class Output
{
    /** @param array<int, SitemapEntry> $entries */
    public function __construct(public array $entries)
    {
    }
}
