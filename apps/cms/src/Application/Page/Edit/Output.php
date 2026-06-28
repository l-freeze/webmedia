<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Edit;

use App\Cms\Application\Media\ImagePicker\Value\Image;
use App\Cms\Application\Page\Edit\Value\Page;

final readonly class Output
{
    /** @param array<int, Image> $media */
    public function __construct(public ?Page $page, public array $media)
    {
    }
}
