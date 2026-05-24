<?php

declare(strict_types=1);

namespace App\Cms\Application\ArticleVersion\Preview\Value;

final readonly class ArticleVersion
{
    public function __construct(
        public int $articleId,
        public int $versionNo,
        public string $title,
        public string $bodyMarkdown,
        public string $status,
        public string $statusLabel,
        public string $createdAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(string $bodyHtml): array
    {
        return [
            'article_id' => $this->articleId,
            'version_no' => $this->versionNo,
            'title' => $this->title,
            'body_markdown' => $this->bodyMarkdown,
            'body_html' => $bodyHtml,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'created_at' => $this->createdAt,
        ];
    }
}
