<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\List;

use App\Cms\Application\Inquiry\List\Value\Inquiry;

final readonly class Output
{
    /** @param array<int, Inquiry> $rows */
    public function __construct(public array $rows)
    {
    }
}
