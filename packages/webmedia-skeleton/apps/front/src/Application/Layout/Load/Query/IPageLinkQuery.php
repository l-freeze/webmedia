<?php

declare(strict_types=1);

namespace App\Front\Application\Layout\Load\Query;

use App\Front\Application\Layout\Load\Value\PageLink;

interface IPageLinkQuery
{
    /** @return array<int, PageLink> */
    public function publishedPageLinks(): array;
}
