<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\MediaAltText;
use App\Cms\Domain\Value\MediaCaption;
use App\Cms\Domain\Value\MediaUploadFile;

final readonly class MediaUpload
{
    public function __construct(
        public MediaUploadFile $file,
        public MediaAltText $altText,
        public MediaCaption $caption,
    ) {
    }
}
