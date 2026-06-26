<?php

declare(strict_types=1);

namespace Tests\Cms\Role;

use App\Cms\Application\Auth\Authorization\Value\PermissionCode;
use App\Cms\Application\Auth\Authorization\Value\RoleCode;
use App\Cms\Application\Role\List\Input;
use App\Cms\Application\Role\List\Interactor;
use App\Cms\Infrastructure\Auth\Authorization\CodeDefinedRolePermissionQuery;
use PHPUnit\Framework\TestCase;

final class RoleListInteractorTest extends TestCase
{
    public function testListsRolesWithTheirPermissions(): void
    {
        $output = (new Interactor(rolePermissions: new CodeDefinedRolePermissionQuery()))->execute(input: new Input());

        $roles = [];
        foreach ($output->roles as $role) {
            $roles[$role->code] = $role->permissions;
        }

        self::assertSame(RoleCode::all(), array_keys($roles));
        self::assertContains(PermissionCode::ADMIN_USER_MANAGE, $roles[RoleCode::ADMIN]);
        self::assertNotContains(PermissionCode::ADMIN_USER_MANAGE, $roles[RoleCode::EDITOR]);
    }
}
