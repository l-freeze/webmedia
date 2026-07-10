<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\MetaDescription;
use App\Cms\Domain\Value\PageBody;
use App\Cms\Domain\Value\PageBodyFormat;
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
        public PageBody $body,
        public PageBodyFormat $bodyFormat,
        public PageStatus $status,
        public PageDisplayOrder $displayOrder,
        public SeoTitle $seoTitle,
        public MetaDescription $metaDescription,
    ) {
    }
}
