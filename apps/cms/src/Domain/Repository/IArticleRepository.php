<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleVersionNo;

interface IArticleRepository
{
    public function findById(ArticleId $articleId): ?Article;

    public function findVersion(ArticleId $articleId, ArticleVersionNo $versionNo): ?Article;

    public function save(Article $article): ArticleId;

    public function slugExists(string $slug, ?int $exceptId): bool;
}
