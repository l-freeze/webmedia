<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Save;

final readonly class Input
{
    private function __construct(
        public string $name,
        public string $slug,
    ) {
    }

    public static function create(string $name, string $slug): self
    {
        return new self(name: trim($name), slug: trim($slug));
    }
}
