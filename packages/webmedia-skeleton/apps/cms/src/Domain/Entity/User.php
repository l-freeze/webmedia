<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\AdminUserEmail;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\AdminUserName;
use App\Cms\Domain\Value\CmsRole;
use App\Cms\Domain\Value\PasswordHash;

final readonly class User
{
    public function __construct(
        public AdminUserId $id,
        public AdminUserEmail $email,
        public PasswordHash $passwordHash,
        public AdminUserName $name,
        public CmsRole $role,
    ) {
    }
}
