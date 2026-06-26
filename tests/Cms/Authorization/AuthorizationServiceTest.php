<?php

declare(strict_types=1);

namespace Tests\Cms\Authorization;

use App\Cms\Application\Auth\Authorization\Exception\AuthorizationException;
use App\Cms\Application\Auth\Authorization\AuthorizationService;
use App\Cms\Infrastructure\Auth\Authorization\CodeDefinedRolePermissionQuery;
use App\Cms\Application\Auth\Authorization\Value\PermissionCode;
use App\Cms\Application\Auth\Authorization\Value\RoleCode;
use App\Cms\Application\Auth\Authorization\RoleHierarchy;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;
use PHPUnit\Framework\TestCase;

final class AuthorizationServiceTest extends TestCase
{
    public function testRolePermissionsMatchInitialCmsPolicy(): void
    {
        $permissions = new CodeDefinedRolePermissionQuery();

        self::assertTrue($permissions->hasPermission(role: RoleCode::ADMIN, permission: PermissionCode::SETTING_UPDATE));
        self::assertTrue($permissions->hasPermission(role: RoleCode::ADMIN, permission: PermissionCode::ADMIN_USER_MANAGE));
        self::assertTrue($permissions->hasPermission(role: RoleCode::ADMIN, permission: PermissionCode::ROLE_VIEW));
        self::assertTrue($permissions->hasPermission(role: RoleCode::ADMIN, permission: PermissionCode::PERMISSION_VIEW));
        self::assertTrue($permissions->hasPermission(role: RoleCode::EDITOR, permission: PermissionCode::ARTICLE_DELETE));
        self::assertTrue($permissions->hasPermission(role: RoleCode::EDITOR, permission: PermissionCode::INQUIRY_UPDATE));
        self::assertFalse($permissions->hasPermission(role: RoleCode::EDITOR, permission: PermissionCode::ADMIN_USER_MANAGE));
        self::assertFalse($permissions->hasPermission(role: RoleCode::EDITOR, permission: PermissionCode::ROLE_VIEW));
        self::assertFalse($permissions->hasPermission(role: RoleCode::EDITOR, permission: PermissionCode::PERMISSION_VIEW));
        self::assertTrue($permissions->hasPermission(role: RoleCode::WRITER, permission: PermissionCode::ARTICLE_UPDATE));
        self::assertFalse($permissions->hasPermission(role: RoleCode::WRITER, permission: PermissionCode::ARTICLE_PUBLISH));
        self::assertFalse($permissions->hasPermission(role: RoleCode::VIEWER, permission: PermissionCode::INQUIRY_READ));
        self::assertFalse($permissions->hasPermission(role: 'unknown', permission: PermissionCode::DASHBOARD_VIEW));
    }

    public function testRoleHierarchyComparesRoleStrength(): void
    {
        $hierarchy = new RoleHierarchy();

        self::assertTrue($hierarchy->isAtLeast(actualRole: RoleCode::EDITOR, requiredRole: RoleCode::WRITER));
        self::assertTrue($hierarchy->isAtLeast(actualRole: RoleCode::ADMIN, requiredRole: RoleCode::EDITOR));
        self::assertFalse($hierarchy->isAtLeast(actualRole: RoleCode::WRITER, requiredRole: RoleCode::EDITOR));
        self::assertFalse($hierarchy->isAtLeast(actualRole: 'unknown', requiredRole: RoleCode::VIEWER));
    }

    public function testAuthorizationServiceCanAndAuthorizeUseRolePermissions(): void
    {
        $service = new AuthorizationService(rolePermissions: new CodeDefinedRolePermissionQuery());
        $editor = $this->user(role: RoleCode::EDITOR);
        $writer = $this->user(role: RoleCode::WRITER);

        self::assertTrue($service->can(user: $editor, permission: PermissionCode::ARTICLE_PUBLISH));
        self::assertFalse($service->can(user: $writer, permission: PermissionCode::ARTICLE_PUBLISH));

        $service->authorize(user: $editor, permission: PermissionCode::ARTICLE_PUBLISH);

        $this->expectException(AuthorizationException::class);
        $service->authorize(user: $writer, permission: PermissionCode::ARTICLE_PUBLISH);
    }

    public function testAuthorizationExceptionCarriesUserAndPermission(): void
    {
        $service = new AuthorizationService(rolePermissions: new CodeDefinedRolePermissionQuery());
        $writer = $this->user(id: 42, role: RoleCode::WRITER);

        try {
            $service->authorize(user: $writer, permission: PermissionCode::SETTING_UPDATE);
            self::fail('AuthorizationException was not thrown.');
        } catch (AuthorizationException $exception) {
            self::assertSame(42, $exception->userId);
            self::assertSame(PermissionCode::SETTING_UPDATE, $exception->permission);
        }
    }

    public function testPermissionNamesAreUnique(): void
    {
        $permissions = PermissionCode::all();

        self::assertSame($permissions, array_values(array_unique($permissions)));
    }

    private function user(int $id = 1, string $role = RoleCode::ADMIN): CurrentCmsUser
    {
        return new CurrentCmsUser(
            id: $id,
            email: 'cms-user@example.test',
            name: 'CMS User',
            role: $role,
        );
    }
}
