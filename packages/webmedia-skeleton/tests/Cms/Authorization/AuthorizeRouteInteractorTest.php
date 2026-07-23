<?php

declare(strict_types=1);

namespace Tests\Cms\Authorization;

use App\Cms\Application\Auth\AuthorizeRoute\Input;
use App\Cms\Application\Auth\AuthorizeRoute\Interactor;
use App\Cms\Application\Auth\AuthorizeRoute\Value\PermissionCode;
use App\Cms\Domain\Value\CmsRole;
use App\Cms\Infrastructure\Auth\Authorization\CodeDefinedRolePermissionQuery;
use PHPUnit\Framework\TestCase;

final class AuthorizeRouteInteractorTest extends TestCase
{
    public function testRolePermissionsMatchInitialCmsPolicy(): void
    {
        $permissions = new CodeDefinedRolePermissionQuery();

        self::assertTrue($permissions->hasPermission(role: CmsRole::Admin->value, permission: PermissionCode::SETTING_UPDATE));
        self::assertTrue($permissions->hasPermission(role: CmsRole::Admin->value, permission: PermissionCode::ADMIN_USER_MANAGE));
        self::assertTrue($permissions->hasPermission(role: CmsRole::Admin->value, permission: PermissionCode::ROLE_VIEW));
        self::assertTrue($permissions->hasPermission(role: CmsRole::Admin->value, permission: PermissionCode::PERMISSION_VIEW));
        self::assertTrue($permissions->hasPermission(role: CmsRole::Editor->value, permission: PermissionCode::ARTICLE_DELETE));
        self::assertTrue($permissions->hasPermission(role: CmsRole::Editor->value, permission: PermissionCode::INQUIRY_UPDATE));
        self::assertFalse($permissions->hasPermission(role: CmsRole::Editor->value, permission: PermissionCode::ADMIN_USER_MANAGE));
        self::assertFalse($permissions->hasPermission(role: CmsRole::Editor->value, permission: PermissionCode::ROLE_VIEW));
        self::assertFalse($permissions->hasPermission(role: CmsRole::Editor->value, permission: PermissionCode::PERMISSION_VIEW));
        self::assertTrue($permissions->hasPermission(role: CmsRole::Writer->value, permission: PermissionCode::ARTICLE_UPDATE));
        self::assertFalse($permissions->hasPermission(role: CmsRole::Writer->value, permission: PermissionCode::ARTICLE_PUBLISH));
        self::assertFalse($permissions->hasPermission(role: CmsRole::Viewer->value, permission: PermissionCode::INQUIRY_READ));
        self::assertFalse($permissions->hasPermission(role: 'unknown', permission: PermissionCode::DASHBOARD_VIEW));
    }

    public function testRoleHierarchyComparesRoleStrength(): void
    {
        self::assertTrue(CmsRole::Editor->isAtLeast(CmsRole::Writer));
        self::assertTrue(CmsRole::Admin->isAtLeast(CmsRole::Editor));
        self::assertFalse(CmsRole::Writer->isAtLeast(CmsRole::Editor));
    }

    public function testAuthorizeRouteInteractorUsesRolePermissions(): void
    {
        $interactor = new Interactor(rolePermissions: new CodeDefinedRolePermissionQuery());

        self::assertTrue($interactor->execute(Input::create(
            actorRole: CmsRole::Editor->value,
            requiredPermission: PermissionCode::ARTICLE_PUBLISH,
        ))->allowed);
        self::assertFalse($interactor->execute(Input::create(
            actorRole: CmsRole::Writer->value,
            requiredPermission: PermissionCode::ARTICLE_PUBLISH,
        ))->allowed);
    }

    public function testPermissionNamesAreUnique(): void
    {
        $permissions = PermissionCode::all();

        self::assertSame($permissions, array_values(array_unique($permissions)));
    }
}
