<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\ImagePicker\Value;

final readonly class Image
{
    public function __construct(
        public int $id,
        public string $fileName,
        public string $path,
        public string $altText,
        public ?int $width = null,
        public ?int $height = null,
    ) {
    }

    /** @return array<string, string|int|null> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->fileName,
            'path' => $this->path,
            'alt_text' => $this->altText,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
