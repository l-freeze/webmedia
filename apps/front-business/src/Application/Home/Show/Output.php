<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Home\Show;

use App\FrontBusiness\Application\Home\Show\Value\Article;
use App\FrontBusiness\Application\Home\Show\Value\Category;
use App\FrontBusiness\Application\Home\Show\Value\Pagination;

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
