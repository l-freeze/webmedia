<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\List;

use App\Cms\Application\Page\List\Value\Page;

final readonly class Output
{
    /** @param array<int, Page> $rows */
    public function __construct(public array $rows)
    {
    }
}
