<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Domain\Repository;

use App\{{ namespace }}\Domain\Entity\HealthCheck;

interface IHealthCheckRepository
{
    public function get(): HealthCheck;
}
