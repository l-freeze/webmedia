<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Update;

use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;

final readonly class Input
{
    private function __construct(
        public int $id,
        public CurrentCmsUser $currentUser,
        public string $email,
        public string $name,
        public string $password,
        public string $role,
    ) {
    }

    public static function create(
        int $id,
        CurrentCmsUser $currentUser,
        string $email,
        string $name,
        string $password,
        string $role,
    ): self {
        return new self(
            id: $id,
            currentUser: $currentUser,
            email: trim($email),
            name: trim($name),
            password: $password,
            role: trim($role),
        );
    }
}
