<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\AdminUser;
use App\Cms\Domain\Value\CmsRole;

interface IAdminUserRepository
{
    public function create(AdminUser $user): int;

    public function update(AdminUser $user): bool;

    public function delete(int $id): bool;

    public function emailExists(string $email, ?int $exceptId): bool;

    public function roleOf(int $id): ?CmsRole;

    public function adminCount(): int;
}
