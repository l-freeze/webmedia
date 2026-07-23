<?php

declare(strict_types=1);

namespace App\Front\Application\Category\Show\Query;

use App\Front\Application\Category\Show\Value\CategoryDetail;

interface ICategoryQuery
{
    public function find(string $slug): ?CategoryDetail;
}
