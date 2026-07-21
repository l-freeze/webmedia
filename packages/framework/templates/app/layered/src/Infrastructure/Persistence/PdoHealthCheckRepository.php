<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Infrastructure\Persistence;

use App\{{ namespace }}\Domain\Entity\HealthCheck;
use App\{{ namespace }}\Domain\Repository\IHealthCheckRepository;
use App\{{ namespace }}\Domain\Value\HealthMessage;
use PDO;

final readonly class PdoHealthCheckRepository implements IHealthCheckRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function get(): HealthCheck
    {
        $databaseOk = $this->pdo->query('SELECT 1') !== false;

        return new HealthCheck(
            status: 'ok',
            message: new HealthMessage(value: '{{ name }} is ready'),
            databaseOk: $databaseOk,
        );
    }
}
