<?php

declare(strict_types=1);

namespace App\Front\Application\Article\Search\Value;

final readonly class Article
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $excerpt,
        public ?int $categoryId,
        public string $categoryName,
        public string $categorySlug,
        public string $eyecatchPath,
        public string $publishedAt,
        public string $updatedAt,
        public string $authorName,
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
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'category_slug' => $this->categorySlug,
            'eyecatch_path' => $this->eyecatchPath,
            'published_at' => $this->publishedAt,
            'updated_at' => $this->updatedAt,
            'author_name' => $this->authorName,
        ];
    }
}
