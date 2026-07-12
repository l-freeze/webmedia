<?php

declare(strict_types=1);

namespace App\Cms\Application\Dashboard\Show;

use App\Cms\Application\Dashboard\Show\Query\IDashboardQuery;
use App\Shared\Time\Application\IClock;

final readonly class Interactor
{
    public function __construct(private IDashboardQuery $dashboard, private IClock $clock)
    {
    }

    public function execute(Input $input): Output
    {
        $stats = $this->dashboard->stats(now: $this->clock->now());

        return new Output(stats: $stats);
    }
}
