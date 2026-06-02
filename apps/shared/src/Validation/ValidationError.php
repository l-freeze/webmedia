<?php

declare(strict_types=1);

namespace App\Shared\Validation;

final readonly class ValidationError
{
    public function __construct(public string $field, public string $message)
    {
    }
}
