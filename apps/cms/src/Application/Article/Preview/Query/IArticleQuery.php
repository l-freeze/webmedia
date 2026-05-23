<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Preview\Query;

use App\Cms\Application\Article\Preview\Value\Article;

interface IArticleQuery
{
    public function find(int $id): ?Article;
}
