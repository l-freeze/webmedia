<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Auth\Authorization;

use App\Cms\Application\Auth\AuthorizeRoute\Query\IRolePermissionQuery as IAuthorizeRouteRolePermissionQuery;
use App\Cms\Application\Auth\AuthorizeRoute\Value\PermissionCode;
use App\Cms\Application\Permission\List\Query\IRolePermissionQuery as IPermissionListRolePermissionQuery;
use App\Cms\Application\Role\List\Query\IRolePermissionQuery as IRoleListRolePermissionQuery;
use App\Cms\Application\Role\List\Value\Permission as RolePermission;
use App\Cms\Domain\Value\CmsRole;

final class CodeDefinedRolePermissionQuery implements IAuthorizeRouteRolePermissionQuery, IPermissionListRolePermissionQuery, IRoleListRolePermissionQuery
{
    /** @var array<string, list<string>> */
    private const PERMISSIONS = [
        'admin' => [
            PermissionCode::DASHBOARD_VIEW,
            PermissionCode::ARTICLE_LIST,
            PermissionCode::ARTICLE_CREATE,
            PermissionCode::ARTICLE_UPDATE,
            PermissionCode::ARTICLE_DELETE,
            PermissionCode::ARTICLE_RESTORE,
            PermissionCode::ARTICLE_PUBLISH,
            PermissionCode::ARTICLE_PREVIEW,
            PermissionCode::ARTICLE_HISTORY,
            PermissionCode::PAGE_LIST,
            PermissionCode::PAGE_CREATE,
            PermissionCode::PAGE_UPDATE,
            PermissionCode::TAXONOMY_MANAGE,
            PermissionCode::MEDIA_LIST,
            PermissionCode::MEDIA_UPLOAD,
            PermissionCode::MEDIA_DELETE,
            PermissionCode::INQUIRY_READ,
            PermissionCode::INQUIRY_UPDATE,
            PermissionCode::AUDIT_VIEW,
            PermissionCode::SETTING_UPDATE,
            PermissionCode::TRASH_VIEW,
            PermissionCode::ADMIN_USER_MANAGE,
            PermissionCode::SECURITY_MANAGE,
            PermissionCode::ROLE_VIEW,
            PermissionCode::PERMISSION_VIEW,
        ],
        'editor' => [
            PermissionCode::DASHBOARD_VIEW,
            PermissionCode::ARTICLE_LIST,
            PermissionCode::ARTICLE_CREATE,
            PermissionCode::ARTICLE_UPDATE,
            PermissionCode::ARTICLE_DELETE,
            PermissionCode::ARTICLE_RESTORE,
            PermissionCode::ARTICLE_PUBLISH,
            PermissionCode::ARTICLE_PREVIEW,
            PermissionCode::ARTICLE_HISTORY,
            PermissionCode::PAGE_LIST,
            PermissionCode::PAGE_CREATE,
            PermissionCode::PAGE_UPDATE,
            PermissionCode::TAXONOMY_MANAGE,
            PermissionCode::MEDIA_LIST,
            PermissionCode::MEDIA_UPLOAD,
            PermissionCode::MEDIA_DELETE,
            PermissionCode::INQUIRY_READ,
            PermissionCode::INQUIRY_UPDATE,
            PermissionCode::TRASH_VIEW,
        ],
        'writer' => [
            PermissionCode::DASHBOARD_VIEW,
            PermissionCode::ARTICLE_LIST,
            PermissionCode::ARTICLE_CREATE,
            PermissionCode::ARTICLE_UPDATE,
            PermissionCode::ARTICLE_PREVIEW,
            PermissionCode::ARTICLE_HISTORY,
            PermissionCode::MEDIA_LIST,
            PermissionCode::MEDIA_UPLOAD,
        ],
        'viewer' => [
            PermissionCode::DASHBOARD_VIEW,
            PermissionCode::ARTICLE_LIST,
            PermissionCode::ARTICLE_PREVIEW,
            PermissionCode::ARTICLE_HISTORY,
            PermissionCode::PAGE_LIST,
            PermissionCode::MEDIA_LIST,
        ],
    ];

    public function hasPermission(string $role, string $permission): bool
    {
        return in_array($permission, $this->permissionCodesForRole(role: $role), true);
    }

    /** @return list<RolePermission> */
    public function permissionsForRole(string $role): array
    {
        $permissions = [];
        foreach ($this->permissionCodesForRole(role: $role) as $permission) {
            $permissions[] = new RolePermission(code: $permission);
        }

        return $permissions;
    }

    /** @return list<string> */
    private function permissionCodesForRole(string $role): array
    {
        $cmsRole = CmsRole::tryFrom($role);

        return $cmsRole === null ? [] : self::PERMISSIONS[$cmsRole->value];
    }
}
