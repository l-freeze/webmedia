<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\List\Value;

final readonly class Page
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $status,
        public int $displayOrder,
        public string $updatedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'display_order' => $this->displayOrder,
            'updated_at' => $this->updatedAt,
        ];
    }
}
