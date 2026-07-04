<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Policy\Query;

interface IRolePermissionQuery
{
    public function hasPermission(string $role, string $permission): bool;

    /**
     * @return list<string>
     */
    public function permissionsForRole(string $role): array;
}
