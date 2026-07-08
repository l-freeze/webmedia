<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

use App\FrontBusiness\Application\Page\Show\Query\IPageQuery;
use App\FrontBusiness\Application\Page\Show\Value\PageDetail;

final readonly class PdoPageQuery implements IPageQuery
{
    public function findPublishedBySlug(string $slug): ?PageDetail
    {
        return null;
    }
}
