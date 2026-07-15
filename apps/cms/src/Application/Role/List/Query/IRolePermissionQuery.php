<?php

declare(strict_types=1);

namespace App\Cms\Application\Role\List\Query;

use App\Cms\Application\Role\List\Value\Permission;

interface IRolePermissionQuery
{
    /** @return list<Permission> */
    public function permissionsForRole(string $role): array;
}
