<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\List\Query;

use App\Cms\Application\Tag\List\Value\Tag;

interface ITagQuery
{
    /** @return array<int, Tag> */
    public function tags(): array;
}
