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
        public ?int $width,
        public ?int $height,
        public ?string $thumbnailPath,
        public ?string $optimizedPath,
        public string $altText,
        public string $caption,
        public string $createdAt,
        /** @var array<int, string> */
        public array $usageLabels = [],
    ) {
    }

    /** @return array<string, string|int|bool|null|array<int, string>> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->fileName,
            'path' => $this->path,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'width' => $this->width,
            'height' => $this->height,
            'thumbnail_path' => $this->thumbnailPath,
            'optimized_path' => $this->optimizedPath,
            'alt_text' => $this->altText,
            'caption' => $this->caption,
            'created_at' => $this->createdAt,
            'usage_labels' => $this->usageLabels,
            'usage_count' => count($this->usageLabels),
            'is_image' => str_starts_with($this->mimeType, 'image/'),
            'is_video' => $this->isVideoMime($this->mimeType),
        ];
    }

    private function isVideoMime(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/')
            || $mimeType === 'application/x-troff-msvideo';
    }
}
