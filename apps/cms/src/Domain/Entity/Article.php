<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\ArticleBodyMarkdown;
use App\Cms\Domain\Value\ArticleExcerpt;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleSlug;
use App\Cms\Domain\Value\ArticleStatus;
use App\Cms\Domain\Value\ArticleTitle;
use App\Cms\Domain\Value\CategoryId;
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
        public ArticleExcerpt $excerpt,
        public ArticleStatus $status,
        public ?CategoryId $categoryId,
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
            id: $id === null ? null : new ArticleId($id),
            title: new ArticleTitle((string) $data['title']),
            slug: new ArticleSlug((string) $data['slug']),
            bodyMarkdown: new ArticleBodyMarkdown((string) $data['body_markdown']),
            excerpt: new ArticleExcerpt((string) ($data['excerpt'] ?? '')),
            status: new ArticleStatus((string) ($data['status'] ?? 'draft')),
            categoryId: ($data['category_id'] ?? '') === '' ? null : new CategoryId((int) $data['category_id']),
            publishedAt: ($data['published_at'] ?? '') === '' ? null : new PublishedAt((string) $data['published_at']),
            publishedEndsAt: ($data['published_ends_at'] ?? '') === '' ? null : new PublishedEndsAt((string) $data['published_ends_at']),
            seoTitle: new SeoTitle((string) ($data['seo_title'] ?? '')),
            metaDescription: new MetaDescription((string) ($data['meta_description'] ?? '')),
        );
    }
}
