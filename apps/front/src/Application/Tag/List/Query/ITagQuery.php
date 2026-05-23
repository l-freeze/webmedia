<?php

declare(strict_types=1);

namespace App\Front\Application\Tag\List\Query;

use App\Front\Application\Tag\List\Value\Tag;

interface ITagQuery
{
    /** @return array<int, Tag> */
    public function all(): array;
}
