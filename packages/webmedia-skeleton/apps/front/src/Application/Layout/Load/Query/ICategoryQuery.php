<?php

declare(strict_types=1);

namespace App\Front\Application\Layout\Load\Query;

use App\Front\Application\Layout\Load\Value\Category;

interface ICategoryQuery
{
    /** @return array<int, Category> */
    public function all(): array;
}
