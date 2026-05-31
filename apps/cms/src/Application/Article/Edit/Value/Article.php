<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit\Value;

final readonly class Article
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $bodyMarkdown,
        public string $bodyFormat,
        public string $excerpt,
        public string $status,
        public ?int $categoryId,
        public string $eyecatchPath,
        public string $publishedAt,
        public string $publishedEndsAt,
        public string $seoTitle,
        public string $metaDescription,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body_markdown' => $this->bodyMarkdown,
            'body_format' => $this->bodyFormat,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'category_id' => $this->categoryId,
            'eyecatch_path' => $this->eyecatchPath,
            'published_at' => $this->publishedAt,
            'published_ends_at' => $this->publishedEndsAt,
            'seo_title' => $this->seoTitle,
            'meta_description' => $this->metaDescription,
        ];
    }
}
