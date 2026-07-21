<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Domain\Entity;

use App\{{ namespace }}\Domain\Value\HealthMessage;

final readonly class HealthCheck
{
    public function __construct(
        public string $status,
        public HealthMessage $message,
        public bool $databaseOk,
    ) {
    }
}
