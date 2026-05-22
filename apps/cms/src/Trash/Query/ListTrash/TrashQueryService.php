<?php

declare(strict_types=1);

namespace App\Cms\Trash\Query\ListTrash;

interface TrashQueryService
{
    /** @return array<int, array<string, mixed>> */
    public function list(): array;
}
