<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class MediaUploadResult
{
    public function __construct(public bool $uploaded, public ?string $error = null)
    {
    }
}
