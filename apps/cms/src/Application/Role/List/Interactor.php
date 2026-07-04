<?php

declare(strict_types=1);

namespace App\Cms\Application\Role\List;

use App\Cms\Application\Role\List\Query\IRolePermissionQuery;
use App\Cms\Application\Role\List\Value\Role;
use App\Cms\Domain\Value\CmsRole;

final readonly class Interactor
{
    public function __construct(private IRolePermissionQuery $rolePermissions)
    {
    }

    public function execute(Input $input): Output
    {
        $roles = [];
        foreach (CmsRole::all() as $role) {
            $roles[] = new Role(
                code: $role,
                permissions: $this->rolePermissions->permissionsForRole($role),
            );
        }

        return new Output(roles: $roles);
    }
}
