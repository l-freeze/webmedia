<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Media;

final readonly class ImageVariantPaths
{
    public function __construct(
        public ?string $thumbnailPath = null,
        public ?string $optimizedPath = null,
    ) {
    }
}
