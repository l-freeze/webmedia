<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\History\Value;

final readonly class ArticleVersion
{
    public function __construct(
        public int $versionNo,
        public string $title,
        public string $status,
        public string $createdAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'version_no' => $this->versionNo,
            'title' => $this->title,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
