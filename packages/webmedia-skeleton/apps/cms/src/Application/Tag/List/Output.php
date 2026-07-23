<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\List;

use App\Cms\Application\Tag\List\Value\Tag;

final readonly class Output
{
    /** @param array<int, Tag> $tags */
    public function __construct(public array $tags)
    {
    }
}
