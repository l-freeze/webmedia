<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Delete;

final readonly class Input
{
    private function __construct(public int $id)
    {
    }

    public static function create(int $id): self
    {
        return new self(id: $id);
    }
}
