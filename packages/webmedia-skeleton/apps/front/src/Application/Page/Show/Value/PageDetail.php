<?php

declare(strict_types=1);

namespace App\Front\Application\Page\Show\Value;

final readonly class PageDetail
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $bodyHtml,
        public string $updatedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body_html' => $this->bodyHtml,
            'updated_at' => $this->updatedAt,
        ];
    }
}
