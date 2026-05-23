<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\List\Query;

use App\Cms\Application\Article\List\Value\Article;

interface IArticleQuery
{
    /** @return array<int, Article> */
    public function list(string $keyword = ''): array;
}
