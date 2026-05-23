<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\List;

use App\Cms\Application\Taxonomy\List\Value\Category;
use App\Cms\Application\Taxonomy\List\Value\Tag;

final readonly class Output
{
    /** @param array<int, Category|Tag> $rows */
    public function __construct(public array $rows)
    {
    }
}
