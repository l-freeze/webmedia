<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Category\Show\Query;

use App\FrontBusiness\Application\Category\Show\Value\CategoryDetail;

interface ICategoryQuery
{
    public function find(string $slug): ?CategoryDetail;
}
