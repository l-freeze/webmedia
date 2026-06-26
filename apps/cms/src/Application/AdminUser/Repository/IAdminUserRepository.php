<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Repository;

interface IAdminUserRepository
{
    /** @param array{email:string,name:string,role:string,password_hash:string} $data */
    public function create(array $data): int;

    /** @param array{email:string,name:string,role:string,password_hash?:string} $data */
    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function emailExists(string $email, ?int $exceptId): bool;

    public function roleOf(int $id): ?string;

    public function adminCount(): int;
}
