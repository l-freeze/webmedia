<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Category\Show\Query;

use App\FrontBusiness\Application\Category\Show\Value\Article;

interface IArticleQuery
{
    /** @return array<int, Article> */
    public function articles(string $slug): array;
}
