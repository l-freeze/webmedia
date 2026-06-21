<?php

declare(strict_types=1);

namespace Tests\Cms\Authorization;

use App\Cms\Application\Auth\Authorization\AuthorizationException;
use App\Cms\Application\Auth\Authorization\AuthorizationService;
use App\Cms\Application\Auth\Authorization\Permission;
use App\Cms\Application\Auth\Authorization\Role;
use App\Cms\Application\Auth\Authorization\RoleHierarchy;
use App\Cms\Application\Auth\Authorization\RolePermissions;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;
use PHPUnit\Framework\TestCase;

final class AuthorizationServiceTest extends TestCase
{
    public function testRolePermissionsMatchInitialCmsPolicy(): void
    {
        $permissions = new RolePermissions();

        self::assertTrue($permissions->roleHasPermission(role: Role::ADMIN, permission: Permission::SETTING_UPDATE));
        self::assertTrue($permissions->roleHasPermission(role: Role::EDITOR, permission: Permission::ARTICLE_DELETE));
        self::assertTrue($permissions->roleHasPermission(role: Role::WRITER, permission: Permission::ARTICLE_UPDATE));
        self::assertFalse($permissions->roleHasPermission(role: Role::WRITER, permission: Permission::ARTICLE_PUBLISH));
        self::assertFalse($permissions->roleHasPermission(role: Role::VIEWER, permission: Permission::INQUIRY_READ));
        self::assertFalse($permissions->roleHasPermission(role: 'unknown', permission: Permission::DASHBOARD_VIEW));
    }

    public function testRoleHierarchyComparesRoleStrength(): void
    {
        $hierarchy = new RoleHierarchy();

        self::assertTrue($hierarchy->isAtLeast(actualRole: Role::EDITOR, requiredRole: Role::WRITER));
        self::assertTrue($hierarchy->isAtLeast(actualRole: Role::ADMIN, requiredRole: Role::EDITOR));
        self::assertFalse($hierarchy->isAtLeast(actualRole: Role::WRITER, requiredRole: Role::EDITOR));
        self::assertFalse($hierarchy->isAtLeast(actualRole: 'unknown', requiredRole: Role::VIEWER));
    }

    public function testAuthorizationServiceCanAndAuthorizeUseRolePermissions(): void
    {
        $service = new AuthorizationService(rolePermissions: new RolePermissions());
        $editor = $this->user(role: Role::EDITOR);
        $writer = $this->user(role: Role::WRITER);

        self::assertTrue($service->can(user: $editor, permission: Permission::ARTICLE_PUBLISH));
        self::assertFalse($service->can(user: $writer, permission: Permission::ARTICLE_PUBLISH));

        $service->authorize(user: $editor, permission: Permission::ARTICLE_PUBLISH);

        $this->expectException(AuthorizationException::class);
        $service->authorize(user: $writer, permission: Permission::ARTICLE_PUBLISH);
    }

    public function testAuthorizationExceptionCarriesUserAndPermission(): void
    {
        $service = new AuthorizationService(rolePermissions: new RolePermissions());
        $writer = $this->user(id: 42, role: Role::WRITER);

        try {
            $service->authorize(user: $writer, permission: Permission::SETTING_UPDATE);
            self::fail('AuthorizationException was not thrown.');
        } catch (AuthorizationException $exception) {
            self::assertSame(42, $exception->userId);
            self::assertSame(Permission::SETTING_UPDATE, $exception->permission);
        }
    }

    public function testPermissionNamesAreUnique(): void
    {
        $permissions = Permission::all();

        self::assertSame($permissions, array_values(array_unique($permissions)));
    }

    private function user(int $id = 1, string $role = Role::ADMIN): CurrentCmsUser
    {
        return new CurrentCmsUser(
            id: $id,
            email: 'cms-user@example.test',
            name: 'CMS User',
            role: $role,
        );
    }
}
