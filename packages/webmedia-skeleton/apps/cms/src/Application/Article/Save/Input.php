<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;

final readonly class Input
{
    private function __construct(
        public ?int $id,
        public CurrentCmsUser $currentUser,
        public string $title,
        public string $slug,
        public string $bodyFormat,
        public string $body,
        public string $excerpt,
        public string $status,
        public string $categoryId,
        public string $eyecatchPath,
        public string $publishedAt,
        public string $publishedEndsAt,
        public string $seoTitle,
        public string $metaDescription,
    ) {
    }

    public static function create(
        ?int $id,
        CurrentCmsUser $currentUser,
        string $title,
        string $slug,
        string $bodyFormat,
        string $body,
        string $excerpt,
        string $status,
        string $categoryId,
        string $eyecatchPath,
        string $publishedAt,
        string $publishedEndsAt,
        string $seoTitle,
        string $metaDescription,
    ): self {
        return new self(
            id: $id,
            currentUser: $currentUser,
            title: trim($title),
            slug: trim($slug),
            bodyFormat: trim($bodyFormat),
            body: $body,
            excerpt: trim($excerpt),
            status: trim($status),
            categoryId: trim($categoryId),
            eyecatchPath: trim($eyecatchPath),
            publishedAt: trim($publishedAt),
            publishedEndsAt: trim($publishedEndsAt),
            seoTitle: trim($seoTitle),
            metaDescription: trim($metaDescription),
        );
    }
}
