<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class CategoryDescription
{
    public function __construct(public string $value)
    {
    }
}
