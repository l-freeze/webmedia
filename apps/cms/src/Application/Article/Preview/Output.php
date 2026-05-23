<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Preview;

use App\Cms\Application\Article\Preview\Value\Article;

final readonly class Output
{
    public function __construct(public ?Article $article)
    {
    }
}
