<?php

declare(strict_types=1);

namespace App\Cms\Application\Permission\List\Query;

interface IRolePermissionQuery
{
    public function hasPermission(string $role, string $permission): bool;
}
