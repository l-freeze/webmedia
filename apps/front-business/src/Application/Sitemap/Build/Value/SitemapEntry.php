<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Sitemap\Build\Value;

final readonly class SitemapEntry
{
    public function __construct(public string $path, public ?string $lastmod = null)
    {
    }
}
