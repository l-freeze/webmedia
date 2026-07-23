<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Edit\Query;

use App\Cms\Application\Page\Edit\Value\Image;

interface IImageQuery
{
    /** @return array<int, Image> */
    public function images(): array;
}
