<?php

declare(strict_types=1);

namespace App\Cms\Application\ArticleVersion\Preview\Query;

use App\Cms\Application\ArticleVersion\Preview\Value\ArticleVersion;

interface IArticleVersionQuery
{
    public function find(int $articleId, int $versionNo): ?ArticleVersion;
}
