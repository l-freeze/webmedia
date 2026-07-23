<?php

declare(strict_types=1);

namespace App\Front\Application\Category\List\Query;

use App\Front\Application\Category\List\Value\Category;

interface ICategoryQuery
{
    /** @return array<int, Category> */
    public function all(): array;
}
