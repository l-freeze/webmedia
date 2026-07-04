<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Auth\Authorization;

use App\Cms\Application\Auth\Policy\Query\IRolePermissionQuery;
use App\Cms\Application\Auth\Policy\Value\PermissionCode;
use App\Cms\Application\Auth\Policy\Value\RoleCode;

final class CodeDefinedRolePermissionQuery implements IRolePermissionQuery
{
    /** @var array<string, list<string>> */
    private const PERMISSIONS = [
        RoleCode::ADMIN => [
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
        RoleCode::EDITOR => [
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
        RoleCode::WRITER => [
            PermissionCode::DASHBOARD_VIEW,
            PermissionCode::ARTICLE_LIST,
            PermissionCode::ARTICLE_CREATE,
            PermissionCode::ARTICLE_UPDATE,
            PermissionCode::ARTICLE_PREVIEW,
            PermissionCode::ARTICLE_HISTORY,
            PermissionCode::MEDIA_LIST,
            PermissionCode::MEDIA_UPLOAD,
        ],
        RoleCode::VIEWER => [
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
        return in_array($permission, $this->permissionsForRole(role: $role), true);
    }

    /**
     * @return list<string>
     */
    public function permissionsForRole(string $role): array
    {
        return self::PERMISSIONS[$role] ?? [];
    }
}
