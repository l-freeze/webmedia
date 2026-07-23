<?php

declare(strict_types=1);

namespace App\Front\Application\Tag\List;

use App\Front\Application\Tag\List\Value\Tag;

final readonly class Output
{
    /** @param array<int, Tag> $items */
    public function __construct(public array $items)
    {
    }
}
