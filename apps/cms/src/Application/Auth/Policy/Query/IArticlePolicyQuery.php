<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Policy\Query;

use App\Cms\Application\Auth\Policy\Value\ArticlePolicyArticle;

interface IArticlePolicyQuery
{
    public function findById(int $id): ?ArticlePolicyArticle;
}
