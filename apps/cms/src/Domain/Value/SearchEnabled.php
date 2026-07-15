<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class SearchEnabled
{
    public function __construct(public bool $value)
    {
    }
}
