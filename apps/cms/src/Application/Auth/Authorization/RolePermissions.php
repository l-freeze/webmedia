<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Authorization;

final class RolePermissions
{
    /** @var array<string, list<string>> */
    private const PERMISSIONS = [
        Role::ADMIN => [
            Permission::DASHBOARD_VIEW,
            Permission::ARTICLE_LIST,
            Permission::ARTICLE_CREATE,
            Permission::ARTICLE_UPDATE,
            Permission::ARTICLE_DELETE,
            Permission::ARTICLE_RESTORE,
            Permission::ARTICLE_PUBLISH,
            Permission::ARTICLE_PREVIEW,
            Permission::ARTICLE_HISTORY,
            Permission::PAGE_LIST,
            Permission::PAGE_CREATE,
            Permission::PAGE_UPDATE,
            Permission::TAXONOMY_MANAGE,
            Permission::MEDIA_LIST,
            Permission::MEDIA_UPLOAD,
            Permission::MEDIA_DELETE,
            Permission::INQUIRY_READ,
            Permission::SETTING_UPDATE,
            Permission::TRASH_VIEW,
        ],
        Role::EDITOR => [
            Permission::DASHBOARD_VIEW,
            Permission::ARTICLE_LIST,
            Permission::ARTICLE_CREATE,
            Permission::ARTICLE_UPDATE,
            Permission::ARTICLE_DELETE,
            Permission::ARTICLE_RESTORE,
            Permission::ARTICLE_PUBLISH,
            Permission::ARTICLE_PREVIEW,
            Permission::ARTICLE_HISTORY,
            Permission::PAGE_LIST,
            Permission::PAGE_CREATE,
            Permission::PAGE_UPDATE,
            Permission::TAXONOMY_MANAGE,
            Permission::MEDIA_LIST,
            Permission::MEDIA_UPLOAD,
            Permission::MEDIA_DELETE,
            Permission::INQUIRY_READ,
            Permission::TRASH_VIEW,
        ],
        Role::WRITER => [
            Permission::DASHBOARD_VIEW,
            Permission::ARTICLE_LIST,
            Permission::ARTICLE_CREATE,
            Permission::ARTICLE_UPDATE,
            Permission::ARTICLE_PREVIEW,
            Permission::ARTICLE_HISTORY,
            Permission::MEDIA_LIST,
            Permission::MEDIA_UPLOAD,
        ],
        Role::VIEWER => [
            Permission::DASHBOARD_VIEW,
            Permission::ARTICLE_LIST,
            Permission::ARTICLE_PREVIEW,
            Permission::ARTICLE_HISTORY,
            Permission::PAGE_LIST,
            Permission::MEDIA_LIST,
        ],
    ];

    public function roleHasPermission(string $role, string $permission): bool
    {
        $permissions = self::PERMISSIONS[$role] ?? [];

        return in_array($permission, $permissions, true);
    }

    /**
     * @return list<string>
     */
    public function permissionsForRole(string $role): array
    {
        return self::PERMISSIONS[$role] ?? [];
    }
}
