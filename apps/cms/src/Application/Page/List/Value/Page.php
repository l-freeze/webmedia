<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\List\Value;

final readonly class Page
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $status,
        public string $updatedAt,
    ) {
    }

    /** @return array<int, string> */
    public function toArray(): array
    {
        return [$this->title, $this->slug, $this->status, $this->updatedAt];
    }
}
