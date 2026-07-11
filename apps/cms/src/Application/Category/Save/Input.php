<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Save;

final readonly class Input
{
    private function __construct(
        public string $name,
        public string $slug,
        public string $description,
    ) {
    }

    public static function create(string $name, string $slug, string $description): self
    {
        return new self(name: trim($name), slug: trim($slug), description: trim($description));
    }
}
