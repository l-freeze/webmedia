<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Sitemap\Build\Query;

use App\FrontBusiness\Application\Sitemap\Build\Value\SitemapEntry;

interface ISitemapEntryQuery
{
    /** @return array<int, SitemapEntry> */
    public function publicEntries(): array;
}
