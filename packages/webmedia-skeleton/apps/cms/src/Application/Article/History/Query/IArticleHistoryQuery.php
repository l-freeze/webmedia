<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\History\Query;

use App\Cms\Application\Article\History\Value\ArticleVersion;

interface IArticleHistoryQuery
{
    /** @return array<int, ArticleVersion> */
    public function versions(int $articleId): array;
}
