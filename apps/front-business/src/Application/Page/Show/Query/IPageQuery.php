<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Page\Show\Query;

use App\FrontBusiness\Application\Page\Show\Value\PageDetail;

interface IPageQuery
{
    public function findPublishedBySlug(string $slug): ?PageDetail;
}
