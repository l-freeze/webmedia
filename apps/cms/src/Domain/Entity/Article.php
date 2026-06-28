<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\ArticleBody;
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
