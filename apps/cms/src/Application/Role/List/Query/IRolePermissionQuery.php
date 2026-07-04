<?php

declare(strict_types=1);

namespace App\Cms\Application\Role\List\Query;

interface IRolePermissionQuery
{
    /** @return list<string> */
    public function permissionsForRole(string $role): array;
}
