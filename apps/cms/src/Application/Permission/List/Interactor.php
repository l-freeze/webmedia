<?php

declare(strict_types=1);

namespace App\Cms\Application\Permission\List;

use App\Cms\Application\Auth\AuthorizeRoute\Value\PermissionCode;
use App\Cms\Application\Permission\List\Query\IRolePermissionQuery;
use App\Cms\Application\Permission\List\Value\Permission;
use App\Cms\Domain\Value\CmsRole;

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
            foreach (CmsRole::all() as $role) {
                $allowed = $this->rolePermissions->hasPermission(role: $role, permission: $permission);
                if ($allowed) {
                    $roles[] = $role;
                }
            }

            $permissions[] = new Permission(code: $permission, roles: $roles);
        }

        return new Output(permissions: $permissions);
    }
}
