<?php

declare(strict_types=1);

namespace App\Front\Application\Category\Show;

use App\Front\Application\Category\Show\Value\Article;
use App\Front\Application\Category\Show\Value\CategoryDetail;

final readonly class Output
{
    /** @param array<int, Article> $articles */
    public function __construct(public ?CategoryDetail $category, public array $articles)
    {
    }
}
