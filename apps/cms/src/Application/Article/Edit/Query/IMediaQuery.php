<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit\Query;

use App\Cms\Application\Article\Edit\Value\Media;

interface IMediaQuery
{
    /** @return array<int, Media> */
    public function media(): array;
}
