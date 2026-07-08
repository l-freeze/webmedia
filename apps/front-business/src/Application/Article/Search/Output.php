<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Article\Search;

use App\FrontBusiness\Application\Article\Search\Value\Article;

final readonly class Output
{
    /** @param array<int, Article> $articles */
    public function __construct(public string $keyword, public array $articles)
    {
    }
}
