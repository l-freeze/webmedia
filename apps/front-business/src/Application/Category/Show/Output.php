<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Category\Show;

use App\FrontBusiness\Application\Category\Show\Value\Article;
use App\FrontBusiness\Application\Category\Show\Value\CategoryDetail;

final readonly class Output
{
    /** @param array<int, Article> $articles */
    public function __construct(public ?CategoryDetail $category, public array $articles)
    {
    }
}
