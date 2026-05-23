<?php

declare(strict_types=1);

namespace App\Cms\Application\Trash\List\Query;

use App\Cms\Application\Trash\List\Value\Trash;

interface ITrashQuery
{
    /** @return array<int, Trash> */
    public function list(): array;
}
