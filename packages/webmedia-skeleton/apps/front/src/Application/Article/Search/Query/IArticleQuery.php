<?php

declare(strict_types=1);

namespace App\Front\Application\Article\Search\Query;

use App\Front\Application\Article\Search\Value\Article;

interface IArticleQuery
{
    /** @return array<int, Article> */
    public function search(string $keyword): array;
}
