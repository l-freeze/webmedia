<?php

declare(strict_types=1);

namespace App\Front\Application\Page\Show;

final readonly class Input
{
    private function __construct(public string $slug)
    {
    }

    public static function create(string $slug): self
    {
        return new self(slug: trim($slug));
    }
}
