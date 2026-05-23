<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\List\Value;

final readonly class Category
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $description,
    ) {
    }

    /** @return array<int, string> */
    public function toArray(): array
    {
        return [$this->name, $this->slug, $this->description];
    }
}
