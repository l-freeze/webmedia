<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Category\List;

use App\FrontBusiness\Application\Category\List\Value\Category;

final readonly class Output
{
    /** @param array<int, Category> $items */
    public function __construct(public array $items)
    {
    }
}
