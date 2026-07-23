<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class MediaId
{
    public function __construct(public int $value)
    {
    }
}
