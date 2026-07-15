<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\AdminUser;
use App\Cms\Domain\Value\AdminUserEmail;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\CmsRole;

interface IAdminUserRepository
{
    public function create(AdminUser $user): AdminUserId;

    public function update(AdminUser $user): bool;

    public function delete(AdminUserId $id): bool;

    public function emailExists(AdminUserEmail $email, ?AdminUserId $exceptId): bool;

    public function roleOf(AdminUserId $id): ?CmsRole;

    public function adminCount(): int;
}
