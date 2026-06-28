<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\ImagePicker\Query;

use App\Cms\Application\Media\ImagePicker\Value\Image;

interface IImageQuery
{
    /** @return array<int, Image> */
    public function images(): array;
}
