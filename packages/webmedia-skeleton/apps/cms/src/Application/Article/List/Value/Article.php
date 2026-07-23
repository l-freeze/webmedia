<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\List\Value;

final readonly class Article
{
    public function __construct(
        public int $id,
        public string $title,
        public string $status,
        public string $publishedAt,
        public string $publishedEndsAt,
        public ?string $categoryName,
        public string $updatedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'category_name' => $this->categoryName,
            'updated_at' => $this->updatedAt,
        ];
    }
}
