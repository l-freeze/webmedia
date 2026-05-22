<?php

declare(strict_types=1);

namespace App\Cms\Dashboard\Query\ShowDashboard;

interface DashboardQueryService
{
    /** @return array<string, int> */
    public function stats(): array;
}
