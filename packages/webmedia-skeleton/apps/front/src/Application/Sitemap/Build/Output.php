<?php

declare(strict_types=1);

namespace App\Front\Application\Sitemap\Build;

use App\Front\Application\Sitemap\Build\Value\SitemapEntry;

final readonly class Output
{
    /** @param array<int, SitemapEntry> $entries */
    public function __construct(public array $entries)
    {
    }
}
