<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\List;

use App\Cms\Application\Article\List\Value\Article;

final readonly class Output
{
    /** @param array<int, Article> $articles */
    public function __construct(public string $keyword, public array $articles)
    {
    }
}
