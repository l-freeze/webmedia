<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\List\Value;

final readonly class Media
{
    public function __construct(
        public int $id,
        public string $fileName,
        public string $path,
        public string $mimeType,
        public int $fileSize,
        public string $altText,
        public string $caption,
        public string $createdAt,
    ) {
    }

    /** @return array<string, string|int|bool> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->fileName,
            'path' => $this->path,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'alt_text' => $this->altText,
            'caption' => $this->caption,
            'created_at' => $this->createdAt,
            'is_image' => str_starts_with($this->mimeType, 'image/'),
            'is_video' => $this->isVideoMime(mimeType: $this->mimeType),
        ];
    }

    private function isVideoMime(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/')
            || $mimeType === 'application/x-troff-msvideo';
    }
}
