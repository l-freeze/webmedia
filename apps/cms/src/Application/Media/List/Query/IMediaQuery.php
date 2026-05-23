<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\List\Query;

use App\Cms\Application\Media\List\Value\Media;

interface IMediaQuery
{
    /** @return array<int, Media> */
    public function list(): array;
}
