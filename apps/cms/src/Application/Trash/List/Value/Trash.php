<?php

declare(strict_types=1);

namespace App\Cms\Application\Trash\List\Value;

final readonly class Trash
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $updatedAt,
    ) {
    }

    /** @return array<int, string> */
    public function toArray(): array
    {
        return [$this->title, $this->slug, $this->updatedAt];
    }
}
