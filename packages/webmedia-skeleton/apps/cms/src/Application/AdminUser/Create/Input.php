<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Create;

final readonly class Input
{
    private function __construct(
        public string $email,
        public string $name,
        public string $password,
        public string $role,
    ) {
    }

    public static function create(string $email, string $name, string $password, string $role): self
    {
        return new self(
            email: trim($email),
            name: trim($name),
            password: $password,
            role: trim($role),
        );
    }
}
