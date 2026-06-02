<?php

declare(strict_types=1);

namespace App\Shared\Validation;

final readonly class ValidationInput
{
    /** @param array<string, mixed> $values */
    public function __construct(public array $values)
    {
    }

    public function string(string $field): string
    {
        $value = $this->values[$field] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
