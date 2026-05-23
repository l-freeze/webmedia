<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleVersionNo;

interface IArticleRepository
{
    public function save(Article $article): ArticleId;

    public function restore(ArticleId $articleId, ArticleVersionNo $versionNo): void;
}
