<?php

declare(strict_types=1);

namespace App\Front\Application\Tag\Show;

use App\Front\Application\Tag\Show\Value\Article;
use App\Front\Application\Tag\Show\Value\TagDetail;

final readonly class Output
{
    /** @param array<int, Article> $articles */
    public function __construct(public ?TagDetail $tag, public array $articles)
    {
    }
}
