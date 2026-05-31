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
            title: new PageTitle(value: (string) $data['title']),
            slug: new PageSlug(value: (string) $data['slug']),
            bodyMarkdown: new PageBodyMarkdown(value: (string) ($data['body_markdown'] ?? '')),
            status: new PageStatus(value: (string) ($data['status'] ?? 'draft')),
            displayOrder: new PageDisplayOrder(value: (int) ($data['display_order'] ?? 0)),
            seoTitle: new SeoTitle(value: (string) ($data['seo_title'] ?? '')),
            metaDescription: new MetaDescription(value: (string) ($data['meta_description'] ?? '')),
        );
    }
}
