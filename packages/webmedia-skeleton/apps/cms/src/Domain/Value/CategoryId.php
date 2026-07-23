<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class CategoryId
{
    public function __construct(public int $value)
    {
    }
}
