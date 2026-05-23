<?php

declare(strict_types=1);

namespace App\Front\Application\Page\Show\Query;

use App\Front\Application\Page\Show\Value\PageDetail;

interface IPageQuery
{
    public function findPublishedBySlug(string $slug): ?PageDetail;
}
