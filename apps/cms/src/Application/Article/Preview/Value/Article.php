<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Preview\Value;

final readonly class Article
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $body,
        public string $bodyFormat,
        public string $status,
        public string $statusLabel,
        public string $eyecatchPath,
        public string $publishedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(string $bodyHtml): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'body_format' => $this->bodyFormat,
            'rendered_body_html' => $bodyHtml,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'eyecatch_path' => $this->eyecatchPath,
            'published_at' => $this->publishedAt,
        ];
    }
}
