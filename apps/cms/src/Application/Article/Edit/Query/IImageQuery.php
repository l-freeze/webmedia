<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit\Query;

use App\Cms\Application\Article\Edit\Value\Image;

interface IImageQuery
{
    /** @return array<int, Image> */
    public function images(): array;
}
