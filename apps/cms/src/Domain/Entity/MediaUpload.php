<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

final readonly class MediaUpload
{
    public function __construct(
        public string $originalName,
        public string $tmpName,
        public int $error,
        public int $size,
        public string $altText,
        public string $caption,
    ) {
    }
}
