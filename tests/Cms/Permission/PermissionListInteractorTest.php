<?php

declare(strict_types=1);

namespace Tests\Cms\Permission;

use App\Cms\Application\Auth\AuthorizeRoute\Value\PermissionCode;
use App\Cms\Domain\Value\CmsRole;
use App\Cms\Application\Permission\List\Input;
use App\Cms\Application\Permission\List\Interactor;
use App\Cms\Infrastructure\Auth\Authorization\CodeDefinedRolePermissionQuery;
use PHPUnit\Framework\TestCase;

final class PermissionListInteractorTest extends TestCase
{
    public function testListsPermissionsWithGrantedRoles(): void
    {
        $output = (new Interactor(rolePermissions: new CodeDefinedRolePermissionQuery()))->execute(input: new Input());

        $permissions = [];
        foreach ($output->permissions as $permission) {
            $permissions[$permission->code] = $permission->roles;
        }

        self::assertSame(PermissionCode::all(), array_keys($permissions));
        self::assertSame([CmsRole::ADMIN], $permissions[PermissionCode::ADMIN_USER_MANAGE]);
        self::assertContains(CmsRole::EDITOR, $permissions[PermissionCode::ARTICLE_UPDATE]);
    }
}
