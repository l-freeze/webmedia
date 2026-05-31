<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\ArticleBodyMarkdown;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleExcerpt;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleSlug;
use App\Cms\Domain\Value\ArticleStatus;
use App\Cms\Domain\Value\ArticleTitle;
use App\Cms\Domain\Value\CategoryId;
use App\Cms\Domain\Value\EyecatchPath;
use App\Cms\Domain\Value\MetaDescription;
use App\Cms\Domain\Value\PublishedEndsAt;
use App\Cms\Domain\Value\PublishedAt;
use App\Cms\Domain\Value\SeoTitle;

final readonly class Article
{
    public function __construct(
        public ?ArticleId $id,
        public ArticleTitle $title,
        public ArticleSlug $slug,
        public ArticleBodyMarkdown $bodyMarkdown,
        public ArticleBodyFormat $bodyFormat,
        public ArticleExcerpt $excerpt,
        public ArticleStatus $status,
        public ?CategoryId $categoryId,
        public ?EyecatchPath $eyecatchPath,
        public ?PublishedAt $publishedAt,
        public ?PublishedEndsAt $publishedEndsAt,
        public SeoTitle $seoTitle,
        public MetaDescription $metaDescription,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromInput(?int $id, array $data): self
    {
        return new self(
            id: $id === null ? null : new ArticleId(value: $id),
            title: new ArticleTitle(value: (string) $data['title']),
            slug: new ArticleSlug(value: (string) $data['slug']),
            bodyMarkdown: new ArticleBodyMarkdown(value: (string) $data['body_markdown']),
            bodyFormat: new ArticleBodyFormat(value: (string) ($data['body_format'] ?? ArticleBodyFormat::MARKDOWN)),
            excerpt: new ArticleExcerpt(value: (string) ($data['excerpt'] ?? '')),
            status: new ArticleStatus(value: (string) ($data['status'] ?? 'draft')),
            categoryId: ($data['category_id'] ?? '') === '' ? null : new CategoryId(value: (int) $data['category_id']),
            eyecatchPath: ($data['eyecatch_path'] ?? '') === '' ? null : new EyecatchPath(value: (string) $data['eyecatch_path']),
            publishedAt: ($data['published_at'] ?? '') === '' ? null : new PublishedAt(value: (string) $data['published_at']),
            publishedEndsAt: ($data['published_ends_at'] ?? '') === '' ? null : new PublishedEndsAt(value: (string) $data['published_ends_at']),
            seoTitle: new SeoTitle(value: (string) ($data['seo_title'] ?? '')),
            metaDescription: new MetaDescription(value: (string) ($data['meta_description'] ?? '')),
        );
    }
}
