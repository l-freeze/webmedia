<?php

declare(strict_types=1);

namespace App\Front\Application\Tag\Show\Query;

use App\Front\Application\Tag\Show\Value\TagDetail;

interface ITagQuery
{
    public function find(string $slug): ?TagDetail;
}
