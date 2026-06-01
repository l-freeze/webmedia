<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\MetaDescription;
use App\Cms\Domain\Value\PageBodyMarkdown;
use App\Cms\Domain\Value\PageDisplayOrder;
use App\Cms\Domain\Value\PageId;
use App\Cms\Domain\Value\PageSlug;
use App\Cms\Domain\Value\PageStatus;
use App\Cms\Domain\Value\PageTitle;
use App\Cms\Domain\Value\SeoTitle;

final readonly class Page
{
    public function __construct(
        public ?PageId $id,
        public PageTitle $title,
        public PageSlug $slug,
        public PageBodyMarkdown $bodyMarkdown,
        public PageStatus $status,
        public PageDisplayOrder $displayOrder,
        public SeoTitle $seoTitle,
        public MetaDescription $metaDescription,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromInput(?int $id, array $data): self
    {
        return new self(
            id: $id === null ? null : new PageId(value: $id),
            title: new PageTitle(value: self::stringValue(data: $data, key: 'title')),
            slug: new PageSlug(value: self::stringValue(data: $data, key: 'slug')),
            bodyMarkdown: new PageBodyMarkdown(value: self::stringValue(data: $data, key: 'body_markdown')),
            status: new PageStatus(value: self::stringValue(data: $data, key: 'status', default: 'draft')),
            displayOrder: new PageDisplayOrder(value: self::intValue(data: $data, key: 'display_order')),
            seoTitle: new SeoTitle(value: self::stringValue(data: $data, key: 'seo_title')),
            metaDescription: new MetaDescription(value: self::stringValue(data: $data, key: 'meta_description')),
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
}
