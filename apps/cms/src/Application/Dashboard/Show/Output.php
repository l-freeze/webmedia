<?php

declare(strict_types=1);

namespace App\Cms\Application\Dashboard\Show;

use App\Cms\Application\Dashboard\Show\Value\Stats;

final readonly class Output
{
    public function __construct(public Stats $stats)
    {
    }
}
