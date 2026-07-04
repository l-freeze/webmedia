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

        self::assertTrue($permissions->hasPermission(role: CmsRole::ADMIN, permission: PermissionCode::SETTING_UPDATE));
        self::assertTrue($permissions->hasPermission(role: CmsRole::ADMIN, permission: PermissionCode::ADMIN_USER_MANAGE));
        self::assertTrue($permissions->hasPermission(role: CmsRole::ADMIN, permission: PermissionCode::ROLE_VIEW));
        self::assertTrue($permissions->hasPermission(role: CmsRole::ADMIN, permission: PermissionCode::PERMISSION_VIEW));
        self::assertTrue($permissions->hasPermission(role: CmsRole::EDITOR, permission: PermissionCode::ARTICLE_DELETE));
        self::assertTrue($permissions->hasPermission(role: CmsRole::EDITOR, permission: PermissionCode::INQUIRY_UPDATE));
        self::assertFalse($permissions->hasPermission(role: CmsRole::EDITOR, permission: PermissionCode::ADMIN_USER_MANAGE));
        self::assertFalse($permissions->hasPermission(role: CmsRole::EDITOR, permission: PermissionCode::ROLE_VIEW));
        self::assertFalse($permissions->hasPermission(role: CmsRole::EDITOR, permission: PermissionCode::PERMISSION_VIEW));
        self::assertTrue($permissions->hasPermission(role: CmsRole::WRITER, permission: PermissionCode::ARTICLE_UPDATE));
        self::assertFalse($permissions->hasPermission(role: CmsRole::WRITER, permission: PermissionCode::ARTICLE_PUBLISH));
        self::assertFalse($permissions->hasPermission(role: CmsRole::VIEWER, permission: PermissionCode::INQUIRY_READ));
        self::assertFalse($permissions->hasPermission(role: 'unknown', permission: PermissionCode::DASHBOARD_VIEW));
    }

    public function testRoleHierarchyComparesRoleStrength(): void
    {
        self::assertTrue(CmsRole::isAtLeast(actualRole: CmsRole::EDITOR, requiredRole: CmsRole::WRITER));
        self::assertTrue(CmsRole::isAtLeast(actualRole: CmsRole::ADMIN, requiredRole: CmsRole::EDITOR));
        self::assertFalse(CmsRole::isAtLeast(actualRole: CmsRole::WRITER, requiredRole: CmsRole::EDITOR));
        self::assertFalse(CmsRole::isAtLeast(actualRole: 'unknown', requiredRole: CmsRole::VIEWER));
    }

    public function testAuthorizeRouteInteractorUsesRolePermissions(): void
    {
        $interactor = new Interactor(rolePermissions: new CodeDefinedRolePermissionQuery());

        self::assertTrue($interactor->execute(new Input(
            actorRole: CmsRole::EDITOR,
            requiredPermission: PermissionCode::ARTICLE_PUBLISH,
        ))->allowed);
        self::assertFalse($interactor->execute(new Input(
            actorRole: CmsRole::WRITER,
            requiredPermission: PermissionCode::ARTICLE_PUBLISH,
        ))->allowed);
    }

    public function testPermissionNamesAreUnique(): void
    {
        $permissions = PermissionCode::all();

        self::assertSame($permissions, array_values(array_unique($permissions)));
    }
}
