<?php

declare(strict_types=1);

namespace App\Front\Application\Article\Search;

final readonly class Input
{
    public function __construct(public string $keyword)
    {
    }
}
