<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Home\Show\Query;

use App\FrontBusiness\Application\Home\Show\Value\Category;

interface ICategoryQuery
{
    /** @return array<int, Category> */
    public function all(): array;
}
