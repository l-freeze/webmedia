<?php

declare(strict_types=1);

namespace App\Front\Application\Home\Show\Query;

use App\Front\Application\Home\Show\Value\Article;
use App\Front\Application\Home\Show\Value\ArticleCount;

interface IArticleQuery
{
    /** @return array<int, Article> */
    public function latest(int $limit, int $offset): array;

    public function countPublished(): ArticleCount;
}
