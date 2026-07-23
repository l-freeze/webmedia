<?php

declare(strict_types=1);

namespace App\Front\Application\Article\Search;

use App\Front\Application\Article\Search\Value\Article;

final readonly class Output
{
    /** @param array<int, Article> $articles */
    public function __construct(public string $keyword, public array $articles)
    {
    }
}
