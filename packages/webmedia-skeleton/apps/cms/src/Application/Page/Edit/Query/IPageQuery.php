<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Edit\Query;

use App\Cms\Application\Page\Edit\Value\Page;

interface IPageQuery
{
    public function find(int $id): ?Page;
}
