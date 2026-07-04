<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\AuthorizeRoute\Query;

interface IRolePermissionQuery
{
    public function hasPermission(string $role, string $permission): bool;
}
