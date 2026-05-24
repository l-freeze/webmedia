<?php

declare(strict_types=1);

namespace App\Cms\Application\ArticleVersion\Preview\Value;

final readonly class ArticleVersion
{
    public function __construct(
        public int $articleId,
        public int $versionNo,
        public string $title,
        public string $slug,
        public string $bodyMarkdown,
        public string $excerpt,
        public string $status,
        public string $statusLabel,
        public ?int $categoryId,
        public string $publishedAt,
        public string $publishedEndsAt,
        public string $seoTitle,
        public string $metaDescription,
        public string $createdAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(string $bodyHtml): array
    {
        return [
            'article_id' => $this->articleId,
            'version_no' => $this->versionNo,
            'title' => $this->title,
            'slug' => $this->slug,
            'body_markdown' => $this->bodyMarkdown,
            'body_html' => $bodyHtml,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'category_id' => $this->categoryId,
            'published_at' => $this->publishedAt,
            'published_ends_at' => $this->publishedEndsAt,
            'seo_title' => $this->seoTitle,
            'meta_description' => $this->metaDescription,
            'created_at' => $this->createdAt,
        ];
    }
}
