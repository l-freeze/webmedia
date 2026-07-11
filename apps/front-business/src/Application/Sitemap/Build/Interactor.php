<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Sitemap\Build;

use App\FrontBusiness\Application\Sitemap\Build\Query\ISitemapEntryQuery;

final readonly class Interactor
{
    public function __construct(private ISitemapEntryQuery $entries)
    {
    }

    public function execute(Input $input): Output
    {
        $entries = $this->entries->publicEntries();

        return new Output(entries: $entries);
    }
}
