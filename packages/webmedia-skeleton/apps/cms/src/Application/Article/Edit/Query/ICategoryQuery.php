<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit\Query;

use App\Cms\Application\Article\Edit\Value\Category;

interface ICategoryQuery
{
    /** @return array<int, Category> */
    public function categories(): array;
}
