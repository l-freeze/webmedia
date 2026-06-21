<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Policy\Query;

use App\Cms\Application\Article\Policy\Value\ArticlePolicyArticle;

interface IArticlePolicyQuery
{
    public function findById(int $id): ?ArticlePolicyArticle;
}
