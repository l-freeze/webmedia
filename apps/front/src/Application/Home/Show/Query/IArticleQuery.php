<?php

declare(strict_types=1);

namespace App\Front\Application\Home\Show\Query;

use App\Front\Application\Home\Show\Value\Article;

interface IArticleQuery
{
    /** @return array<int, Article> */
    public function latest(int $limit = 10): array;
}
