<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\List;

use App\Cms\Application\Category\List\Value\Category;

final readonly class Output
{
    /** @param array<int, Category> $categories */
    public function __construct(public array $categories)
    {
    }
}
