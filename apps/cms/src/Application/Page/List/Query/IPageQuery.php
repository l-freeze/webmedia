<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\List\Query;

use App\Cms\Application\Page\List\Value\Page;

interface IPageQuery
{
    /** @return array<int, Page> */
    public function list(): array;
}
