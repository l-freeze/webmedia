<?php

declare(strict_types=1);

namespace App\Cms\Application\Trash\List;

use App\Cms\Application\Trash\List\Value\Trash;

final readonly class Output
{
    /** @param array<int, Trash> $rows */
    public function __construct(public array $rows)
    {
    }
}
