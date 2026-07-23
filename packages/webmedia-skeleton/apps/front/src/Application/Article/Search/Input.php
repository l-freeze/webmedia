<?php

declare(strict_types=1);

namespace App\Front\Application\Article\Search;

final readonly class Input
{
    private function __construct(public string $keyword)
    {
    }

    public static function create(string $keyword): self
    {
        return new self(keyword: trim($keyword));
    }
}
