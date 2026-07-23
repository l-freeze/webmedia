<?php

declare(strict_types=1);

namespace App\Front\Application\Article\Show;

use App\Front\Application\Article\Show\Value\Article;

final readonly class Output
{
    /** @param array<int, Article> $related */
    public function __construct(public ?Article $article, public array $related = [])
    {
    }
}
