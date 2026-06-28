<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit;

use App\Cms\Application\Article\Edit\Value\Article;
use App\Cms\Application\Article\Edit\Value\Category;
use App\Cms\Application\Media\ImagePicker\Value\Image;

final readonly class Output
{
    /**
     * @param array<int, Category> $categories
     * @param array<int, Image> $media
     */
    public function __construct(public ?Article $article, public array $categories, public array $media)
    {
    }
}
