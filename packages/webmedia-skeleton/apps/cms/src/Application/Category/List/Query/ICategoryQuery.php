<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\List\Query;

use App\Cms\Application\Category\List\Value\Category;

interface ICategoryQuery
{
    /** @return array<int, Category> */
    public function categories(): array;
}
