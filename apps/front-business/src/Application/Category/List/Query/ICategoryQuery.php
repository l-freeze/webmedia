<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Category\List\Query;

use App\FrontBusiness\Application\Category\List\Value\Category;

interface ICategoryQuery
{
    /** @return array<int, Category> */
    public function all(): array;
}
