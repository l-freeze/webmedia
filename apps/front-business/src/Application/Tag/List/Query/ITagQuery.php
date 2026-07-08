<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Tag\List\Query;

use App\FrontBusiness\Application\Tag\List\Value\Tag;

interface ITagQuery
{
    /** @return array<int, Tag> */
    public function all(): array;
}
