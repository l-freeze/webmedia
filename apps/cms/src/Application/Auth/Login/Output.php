<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Login;

final readonly class Output
{
    private function __construct(
        public bool $authenticated,
        public ?int $userId = null,
        public ?string $role = null,
    ) {
    }

    public static function authenticated(int $userId, string $role): self
    {
        return new self(authenticated: true, userId: $userId, role: $role);
    }

    public static function failed(): self
    {
        return new self(authenticated: false);
    }
}
