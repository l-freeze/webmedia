<?php

declare(strict_types=1);

namespace App\Front\Application\Tag\Show\Query;

use App\Front\Application\Tag\Show\Value\Article;

interface IArticleQuery
{
    /** @return array<int, Article> */
    public function articles(string $slug): array;
}
