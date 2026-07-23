<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Edit\Value;

final readonly class Image
{
    public function __construct(
        public int $id,
        public string $fileName,
        public string $path,
        public string $altText,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $thumbnailPath = null,
        public ?string $optimizedPath = null,
    ) {
    }

    /** @return array<string, string|int|null|array<int, array<string, string>>> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->fileName,
            'path' => $this->path,
            'alt_text' => $this->altText,
            'width' => $this->width,
            'height' => $this->height,
            'thumbnail_path' => $this->thumbnailPath,
            'optimized_path' => $this->optimizedPath,
            'variants' => $this->variants(),
        ];
    }

    /** @return array<int, array<string, string>> */
    private function variants(): array
    {
        $variants = [[
            'label' => '元画像',
            'path' => $this->path,
        ]];

        if ($this->optimizedPath !== null && $this->optimizedPath !== '') {
            $variants[] = [
                'label' => '最適化',
                'path' => $this->optimizedPath,
            ];
        }

        if ($this->thumbnailPath !== null && $this->thumbnailPath !== '') {
            $variants[] = [
                'label' => 'サムネイル',
                'path' => $this->thumbnailPath,
            ];
        }

        return $variants;
    }
}
