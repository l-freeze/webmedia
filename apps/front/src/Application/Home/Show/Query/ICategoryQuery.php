<?php

declare(strict_types=1);

namespace App\Front\Application\Home\Show\Query;

use App\Front\Application\Home\Show\Value\Category;

interface ICategoryQuery
{
    /** @return array<int, Category> */
    public function all(): array;
}
