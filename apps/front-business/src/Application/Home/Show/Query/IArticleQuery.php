<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Home\Show\Query;

use App\FrontBusiness\Application\Home\Show\Value\Article;
use App\FrontBusiness\Application\Home\Show\Value\ArticleCount;

interface IArticleQuery
{
    /** @return array<int, Article> */
    public function latest(int $limit, int $offset): array;

    public function countPublished(): ArticleCount;
}
