<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class MediaAltText
{
    public function __construct(public string $value)
    {
    }
}
