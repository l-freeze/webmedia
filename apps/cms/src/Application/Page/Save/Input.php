<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Save;

final readonly class Input
{
    private function __construct(
        public ?int $id,
        public string $title,
        public string $slug,
        public string $bodyFormat,
        public string $body,
        public string $status,
        public string $displayOrder,
        public string $seoTitle,
        public string $metaDescription,
    ) {
    }

    public static function create(
        ?int $id,
        string $title,
        string $slug,
        string $bodyFormat,
        string $body,
        string $status,
        string $displayOrder,
        string $seoTitle,
        string $metaDescription,
    ): self {
        return new self(
            id: $id,
            title: trim($title),
            slug: trim($slug),
            bodyFormat: trim($bodyFormat),
            body: $body,
            status: trim($status),
            displayOrder: trim($displayOrder),
            seoTitle: trim($seoTitle),
            metaDescription: trim($metaDescription),
        );
    }
}
