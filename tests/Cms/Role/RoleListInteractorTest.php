<?php

declare(strict_types=1);

namespace Tests\Cms\Role;

use App\Cms\Application\Auth\AuthorizeRoute\Value\PermissionCode;
use App\Cms\Domain\Value\CmsRole;
use App\Cms\Application\Role\List\Input;
use App\Cms\Application\Role\List\Interactor;
use App\Cms\Infrastructure\Auth\Authorization\CodeDefinedRolePermissionQuery;
use PHPUnit\Framework\TestCase;

final class RoleListInteractorTest extends TestCase
{
    public function testListsRolesWithTheirPermissions(): void
    {
        $output = (new Interactor(rolePermissions: new CodeDefinedRolePermissionQuery()))->execute(input: Input::create());

        $roles = [];
        foreach ($output->roles as $role) {
            $permissions = [];
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->code;
            }
            $roles[$role->code] = $permissions;
        }

        self::assertSame(array_map(static fn (CmsRole $role): string => $role->value, CmsRole::cases()), array_keys($roles));
        self::assertContains(PermissionCode::ADMIN_USER_MANAGE, $roles[CmsRole::Admin->value]);
        self::assertNotContains(PermissionCode::ADMIN_USER_MANAGE, $roles[CmsRole::Editor->value]);
    }
}
