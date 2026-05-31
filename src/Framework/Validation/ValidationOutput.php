<?php

declare(strict_types=1);

namespace App\Framework\Validation;

final readonly class ValidationOutput
{
    /** @param list<ValidationError> $errors */
    public function __construct(public array $errors = [])
    {
    }

    public static function valid(): self
    {
        return new self();
    }

    public static function invalid(ValidationError ...$errors): self
    {
        return new self(errors: array_values($errors));
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function firstMessage(): ?string
    {
        return $this->errors[0]->message ?? null;
    }
}
