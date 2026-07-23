<?php

declare(strict_types=1);

namespace App\Front\Application\Home\Show;

final readonly class Input
{
    private function __construct(public int $page)
    {
    }

    public static function create(int $page = 1): self
    {
        return new self(page: max(1, $page));
    }
}
