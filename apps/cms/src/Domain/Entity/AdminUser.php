<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

final readonly class AdminUser
{
    public function __construct(
        public ?int $id,
        public string $email,
        public string $name,
        public string $role,
        public string $passwordHash,
    ) {
    }
}
