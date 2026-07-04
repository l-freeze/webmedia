<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\ArticleBody;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleCreatePermission;
use App\Cms\Domain\Value\ArticleDeletePermission;
use App\Cms\Domain\Value\ArticleExcerpt;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticlePublishPermission;
use App\Cms\Domain\Value\ArticleRestorePermission;
use App\Cms\Domain\Value\ArticleSlug;
use App\Cms\Domain\Value\ArticleStatus;
use App\Cms\Domain\Value\ArticleTitle;
use App\Cms\Domain\Value\ArticleUpdatePermission;
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
        public ArticleBody $body,
        public ArticleBodyFormat $bodyFormat,
        public ArticleExcerpt $excerpt,
        public ArticleStatus $status,
        public ?CategoryId $categoryId,
        public ?EyecatchPath $eyecatchPath,
        public ?PublishedAt $publishedAt,
        public ?PublishedEndsAt $publishedEndsAt,
        public SeoTitle $seoTitle,
        public MetaDescription $metaDescription,
        public ?int $authorId,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function create(ArticleCreatePermission $permission, array $data, ?ArticlePublishPermission $publishPermission): self
    {
        $article = self::fromInput(id: null, data: $data);
        self::assertPublishPermission(article: $article, permission: $publishPermission);

        return $article;
    }

    /** @param array<string, mixed> $data */
    public static function fromInput(?int $id, array $data): self
    {
        return new self(
            id: $id === null ? null : new ArticleId(value: $id),
            title: new ArticleTitle(value: self::stringValue(data: $data, key: 'title')),
            slug: new ArticleSlug(value: self::stringValue(data: $data, key: 'slug')),
            body: new ArticleBody(value: self::stringValue(data: $data, key: 'body')),
            bodyFormat: new ArticleBodyFormat(value: self::stringValue(data: $data, key: 'body_format', default: ArticleBodyFormat::MARKDOWN)),
            excerpt: new ArticleExcerpt(value: self::stringValue(data: $data, key: 'excerpt')),
            status: new ArticleStatus(value: self::stringValue(data: $data, key: 'status', default: 'draft')),
            categoryId: self::stringValue(data: $data, key: 'category_id') === '' ? null : new CategoryId(value: self::intValue(data: $data, key: 'category_id')),
            eyecatchPath: self::stringValue(data: $data, key: 'eyecatch_path') === '' ? null : new EyecatchPath(value: self::stringValue(data: $data, key: 'eyecatch_path')),
            publishedAt: self::stringValue(data: $data, key: 'published_at') === '' ? null : new PublishedAt(value: self::stringValue(data: $data, key: 'published_at')),
            publishedEndsAt: self::stringValue(data: $data, key: 'published_ends_at') === '' ? null : new PublishedEndsAt(value: self::stringValue(data: $data, key: 'published_ends_at')),
            seoTitle: new SeoTitle(value: self::stringValue(data: $data, key: 'seo_title')),
            metaDescription: new MetaDescription(value: self::stringValue(data: $data, key: 'meta_description')),
            authorId: self::nullablePositiveInt(data: $data, key: 'author_id'),
        );
    }

    /** @param array<string, mixed> $data */
    public function update(ArticleUpdatePermission $permission, array $data, ?ArticlePublishPermission $publishPermission): self
    {
        $article = self::fromInput(id: $this->id?->value, data: $data);
        self::assertPublishPermission(article: $article, permission: $publishPermission);

        return new self(
            id: $this->id,
            title: $article->title,
            slug: $article->slug,
            body: $article->body,
            bodyFormat: $article->bodyFormat,
            excerpt: $article->excerpt,
            status: $article->status,
            categoryId: $article->categoryId,
            eyecatchPath: $article->eyecatchPath,
            publishedAt: $article->publishedAt,
            publishedEndsAt: $article->publishedEndsAt,
            seoTitle: $article->seoTitle,
            metaDescription: $article->metaDescription,
            authorId: $this->authorId,
        );
    }

    public function trash(ArticleDeletePermission $permission): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            slug: $this->slug,
            body: $this->body,
            bodyFormat: $this->bodyFormat,
            excerpt: $this->excerpt,
            status: new ArticleStatus(value: 'trashed'),
            categoryId: $this->categoryId,
            eyecatchPath: $this->eyecatchPath,
            publishedAt: $this->publishedAt,
            publishedEndsAt: $this->publishedEndsAt,
            seoTitle: $this->seoTitle,
            metaDescription: $this->metaDescription,
            authorId: $this->authorId,
        );
    }

    public function restore(ArticleRestorePermission $permission, Article $version): self
    {
        return new self(
            id: $this->id,
            title: $version->title,
            slug: $version->slug,
            body: $version->body,
            bodyFormat: $version->bodyFormat,
            excerpt: $version->excerpt,
            status: $version->status,
            categoryId: $version->categoryId,
            eyecatchPath: $version->eyecatchPath,
            publishedAt: $version->publishedAt,
            publishedEndsAt: $version->publishedEndsAt,
            seoTitle: $version->seoTitle,
            metaDescription: $version->metaDescription,
            authorId: $this->authorId,
        );
    }

    private static function assertPublishPermission(Article $article, ?ArticlePublishPermission $permission): void
    {
        if ($article->status->value === 'published' && $permission === null) {
            throw new \LogicException('Publishing an article requires ArticlePublishPermission.');
        }
    }

    /** @param array<string, mixed> $data */
    private static function stringValue(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;
        return is_scalar($value) ? (string) $value : $default;
    }

    /** @param array<string, mixed> $data */
    private static function intValue(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? null;
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /** @param array<string, mixed> $data */
    private static function nullablePositiveInt(array $data, string $key): ?int
    {
        $value = self::intValue(data: $data, key: $key);

        return $value > 0 ? $value : null;
    }
}
