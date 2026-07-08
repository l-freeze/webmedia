<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

use App\FrontBusiness\Application\Layout\Load\Query\IPageLinkQuery;

final readonly class PdoPageLinkQuery implements IPageLinkQuery
{
    /** @return array<int, \App\FrontBusiness\Application\Layout\Load\Value\PageLink> */
    public function publishedPageLinks(): array
    {
        return [];
    }
}
