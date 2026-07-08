<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Tag\List;

use App\FrontBusiness\Application\Tag\List\Value\Tag;

final readonly class Output
{
    /** @param array<int, Tag> $items */
    public function __construct(public array $items)
    {
    }
}
