<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Edit\Value;

final readonly class Page
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $bodyMarkdown,
        public string $status,
        public string $seoTitle,
        public string $metaDescription,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body_markdown' => $this->bodyMarkdown,
            'status' => $this->status,
            'seo_title' => $this->seoTitle,
            'meta_description' => $this->metaDescription,
        ];
    }
}
