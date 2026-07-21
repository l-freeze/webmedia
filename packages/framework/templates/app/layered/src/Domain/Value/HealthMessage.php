<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Domain\Value;

use InvalidArgumentException;

final readonly class HealthMessage
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('Health message must not be empty.');
        }
    }
}
