<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Application\Health\Show;

use App\{{ namespace }}\Domain\Entity\HealthCheck;

final readonly class Output
{
    public function __construct(public HealthCheck $healthCheck)
    {
    }

    /** @return array{status:string,message:string,database:string} */
    public function toArray(): array
    {
        return [
            'status' => $this->healthCheck->status,
            'message' => $this->healthCheck->message->value,
            'database' => $this->healthCheck->databaseOk ? 'ok' : 'ng',
        ];
    }
}
