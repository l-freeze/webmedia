<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Update;

final readonly class Input
{
    private function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $description,
    ) {
    }

    public static function create(int $id, string $name, string $slug, string $description): self
    {
        return new self(id: $id, name: trim($name), slug: trim($slug), description: trim($description));
    }
}
