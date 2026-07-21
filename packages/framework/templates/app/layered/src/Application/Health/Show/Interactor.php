<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Application\Health\Show;

use App\{{ namespace }}\Domain\Repository\IHealthCheckRepository;

final readonly class Interactor
{
    public function __construct(private IHealthCheckRepository $healthChecks)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(healthCheck: $this->healthChecks->get());
    }
}
