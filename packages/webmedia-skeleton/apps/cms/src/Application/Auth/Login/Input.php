<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Login;

final readonly class Input
{
    private function __construct(
        public string $email,
        public string $password,
        public string $ipAddress,
    ) {
    }

    public static function create(string $email, string $password, string $ipAddress): self
    {
        return new self(
            email: trim($email),
            password: $password,
            ipAddress: trim($ipAddress),
        );
    }
}
