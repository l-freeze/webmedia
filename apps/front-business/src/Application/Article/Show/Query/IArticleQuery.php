<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Article\Show\Query;

use App\FrontBusiness\Application\Article\Show\Value\Article;

interface IArticleQuery
{
    public function findPublishedBySlug(string $slug): ?Article;

    /** @return array<int, Article> */
    public function related(int $articleId, ?int $categoryId): array;
}
