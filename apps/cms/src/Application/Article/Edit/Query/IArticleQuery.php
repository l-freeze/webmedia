<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit\Query;

use App\Cms\Application\Article\Edit\Value\Article;

interface IArticleQuery
{
    public function find(int $id): ?Article;
}
