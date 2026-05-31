<?php

declare(strict_types=1);

namespace App\Framework\Validation;

final readonly class ValidationError
{
    public function __construct(public string $field, public string $message)
    {
    }
}
