<?php

declare(strict_types=1);

namespace App\Front\Application\Home\Show;

use App\Front\Application\Home\Show\Value\Article;
use App\Front\Application\Home\Show\Value\Category;
use App\Front\Application\Home\Show\Value\Pagination;

final readonly class Output
{
    /**
     * @param array<int, Article> $articles
     * @param array<int, Category> $categories
     */
    public function __construct(public array $articles, public array $categories, public Pagination $pagination)
    {
    }
}
