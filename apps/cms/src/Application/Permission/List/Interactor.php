<?php

declare(strict_types=1);

namespace App\Cms\Application\Permission\List;

use App\Cms\Application\Auth\Authorization\Query\IRolePermissionQuery;
use App\Cms\Application\Auth\Authorization\Value\PermissionCode;
use App\Cms\Application\Auth\Authorization\Value\RoleCode;
use App\Cms\Application\Permission\List\Value\Permission;

final readonly class Interactor
{
    public function __construct(private IRolePermissionQuery $rolePermissions)
    {
    }

    public function execute(Input $input): Output
    {
        $permissions = [];
        foreach (PermissionCode::all() as $permission) {
            $roles = [];
            foreach (RoleCode::all() as $role) {
                if ($this->rolePermissions->hasPermission(role: $role, permission: $permission)) {
                    $roles[] = $role;
                }
            }

            $permissions[] = new Permission(code: $permission, roles: $roles);
        }

        return new Output(permissions: $permissions);
    }
}
