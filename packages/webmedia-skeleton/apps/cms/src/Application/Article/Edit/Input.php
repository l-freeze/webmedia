<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit;

final readonly class Input
{
    private function __construct(public ?int $id)
    {
    }

    public static function create(?int $id): self
    {
        return new self(id: $id);
    }
}
