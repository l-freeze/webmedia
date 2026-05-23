<?php

declare(strict_types=1);

namespace App\Cms\Application\Dashboard\Show\Query;

use App\Cms\Application\Dashboard\Show\Value\Stats;

interface IDashboardQuery
{
    public function stats(): Stats;
}
