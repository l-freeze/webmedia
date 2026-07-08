<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Tag\Show\Query;

use App\FrontBusiness\Application\Tag\Show\Value\TagDetail;

interface ITagQuery
{
    public function find(string $slug): ?TagDetail;
}
