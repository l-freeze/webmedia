<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\ResetLoginAttempt;

final readonly class Output
{
    public function __construct(public bool $reset, public ?string $error = null)
    {
    }

    public static function reset(): self
    {
        return new self(reset: true);
    }

    public static function invalid(string $error): self
    {
        return new self(reset: false, error: $error);
    }
}
