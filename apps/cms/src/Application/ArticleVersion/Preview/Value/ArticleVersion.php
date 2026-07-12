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
        public string $body,
        public string $bodyFormat,
        public string $excerpt,
        public string $status,
        public ?int $categoryId,
        public string $eyecatchPath,
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
            'body' => $this->body,
            'body_format' => $this->bodyFormat,
            'rendered_body_html' => $bodyHtml,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'category_id' => $this->categoryId,
            'eyecatch_path' => $this->eyecatchPath,
            'published_at' => $this->publishedAt,
            'published_ends_at' => $this->publishedEndsAt,
            'seo_title' => $this->seoTitle,
            'meta_description' => $this->metaDescription,
            'created_at' => $this->createdAt,
        ];
    }
}
