<?php

declare(strict_types=1);

namespace App\Front\Application\Layout\Query;

use App\Front\Application\Layout\Value\PageLink;

interface IPageLinkQuery
{
    /** @return array<int, PageLink> */
    public function publishedPageLinks(): array;
}
