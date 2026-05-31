<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit;

use App\Cms\Application\Article\Edit\Value\Article;
use App\Cms\Application\Article\Edit\Value\Category;
use App\Cms\Application\Article\Edit\Value\Media;

final readonly class Output
{
    /**
     * @param array<int, Category> $categories
     * @param array<int, Media> $media
     */
    public function __construct(public ?Article $article, public array $categories, public array $media)
    {
    }
}
