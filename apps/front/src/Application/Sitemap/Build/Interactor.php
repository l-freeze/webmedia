<?php

declare(strict_types=1);

namespace App\Front\Application\Sitemap\Build;

use App\Front\Application\Sitemap\Query\ISitemapEntryQuery;

final readonly class Interactor
{
    public function __construct(private ISitemapEntryQuery $entries)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(entries: $this->entries->publicEntries());
    }
}
