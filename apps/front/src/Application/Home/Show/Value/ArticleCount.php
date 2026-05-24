<?php

declare(strict_types=1);

namespace App\Front\Application\Home\Show\Value;

final readonly class ArticleCount
{
    public function __construct(public int $value)
    {
    }
}
