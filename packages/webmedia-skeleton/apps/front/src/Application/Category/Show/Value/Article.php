<?php

declare(strict_types=1);

namespace App\Front\Application\Category\Show\Value;

final readonly class Article
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $excerpt,
        public string $categoryName,
        public string $eyecatchPath,
        public string $publishedAt,
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
            'excerpt' => $this->excerpt,
            'category_name' => $this->categoryName,
            'eyecatch_path' => $this->eyecatchPath,
            'published_at' => $this->publishedAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
