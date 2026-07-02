<?php

declare(strict_types=1);

namespace App\Front\Application\Sitemap\Value;

final readonly class SitemapEntry
{
    public function __construct(public string $path, public ?string $lastmod = null)
    {
    }
}
