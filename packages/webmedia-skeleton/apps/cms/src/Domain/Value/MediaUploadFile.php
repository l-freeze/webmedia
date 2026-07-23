<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class MediaUploadFile
{
    public function __construct(
        public string $originalName,
        public string $temporaryPath,
        public int $uploadError,
        public int $sizeInBytes,
    ) {
    }
}
