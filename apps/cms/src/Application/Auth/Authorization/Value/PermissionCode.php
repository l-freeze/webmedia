<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Authorization\Value;

final class PermissionCode
{
    public const DASHBOARD_VIEW = 'dashboard.view';
    public const ARTICLE_LIST = 'article.list';
    public const ARTICLE_CREATE = 'article.create';
    public const ARTICLE_UPDATE = 'article.update';
    public const ARTICLE_DELETE = 'article.delete';
    public const ARTICLE_RESTORE = 'article.restore';
    public const ARTICLE_PUBLISH = 'article.publish';
    public const ARTICLE_PREVIEW = 'article.preview';
    public const ARTICLE_HISTORY = 'article.history';
    public const PAGE_LIST = 'page.list';
    public const PAGE_CREATE = 'page.create';
    public const PAGE_UPDATE = 'page.update';
    public const TAXONOMY_MANAGE = 'taxonomy.manage';
    public const MEDIA_LIST = 'media.list';
    public const MEDIA_UPLOAD = 'media.upload';
    public const MEDIA_DELETE = 'media.delete';
    public const INQUIRY_READ = 'inquiry.read';
    public const INQUIRY_UPDATE = 'inquiry.update';
    public const AUDIT_VIEW = 'audit.view';
    public const SETTING_UPDATE = 'setting.update';
    public const TRASH_VIEW = 'trash.view';
    public const ADMIN_USER_MANAGE = 'admin_user.manage';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::ARTICLE_LIST,
            self::ARTICLE_CREATE,
            self::ARTICLE_UPDATE,
            self::ARTICLE_DELETE,
            self::ARTICLE_RESTORE,
            self::ARTICLE_PUBLISH,
            self::ARTICLE_PREVIEW,
            self::ARTICLE_HISTORY,
            self::PAGE_LIST,
            self::PAGE_CREATE,
            self::PAGE_UPDATE,
            self::TAXONOMY_MANAGE,
            self::MEDIA_LIST,
            self::MEDIA_UPLOAD,
            self::MEDIA_DELETE,
            self::INQUIRY_READ,
            self::INQUIRY_UPDATE,
            self::AUDIT_VIEW,
            self::SETTING_UPDATE,
            self::TRASH_VIEW,
            self::ADMIN_USER_MANAGE,
        ];
    }
}
