<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\CmsRole;

final readonly class AdminUser
{
    public function __construct(
        public ?int $id,
        public string $email,
        public string $name,
        public CmsRole $role,
        public string $passwordHash,
    ) {
    }
}
