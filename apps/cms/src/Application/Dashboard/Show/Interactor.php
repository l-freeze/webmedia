<?php

declare(strict_types=1);

namespace App\Cms\Application\Dashboard\Show;

use App\Cms\Application\Dashboard\Show\Query\IDashboardQuery;

final readonly class Interactor
{
    public function __construct(private IDashboardQuery $dashboard)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($this->dashboard->stats());
    }
}
