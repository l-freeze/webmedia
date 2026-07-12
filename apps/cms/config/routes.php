<?php

declare(strict_types=1);

use App\Cms\Presentation\Http\Controller\Article\Create\Controller as ArticleCreateController;
use App\Cms\Presentation\Http\Controller\Article\Delete\Controller as ArticleDeleteController;
use App\Cms\Presentation\Http\Controller\Article\Edit\Controller as ArticleEditController;
use App\Cms\Presentation\Http\Controller\Article\History\Controller as ArticleHistoryController;
use App\Cms\Presentation\Http\Controller\Article\List\Controller as ArticleListController;
use App\Cms\Presentation\Http\Controller\Article\Preview\Controller as ArticlePreviewController;
use App\Cms\Presentation\Http\Controller\Article\Restore\Controller as ArticleRestoreController;
use App\Cms\Presentation\Http\Controller\Article\Store\Controller as ArticleStoreController;
use App\Cms\Presentation\Http\Controller\Article\Update\Controller as ArticleUpdateController;
use App\Cms\Presentation\Http\Controller\Article\VersionPreview\Controller as ArticleVersionPreviewController;
use App\Cms\Presentation\Http\Controller\AdminUser\Create\Controller as AdminUserCreateController;
use App\Cms\Presentation\Http\Controller\AdminUser\Delete\Controller as AdminUserDeleteController;
use App\Cms\Presentation\Http\Controller\AdminUser\List\Controller as AdminUserListController;
use App\Cms\Presentation\Http\Controller\AdminUser\Update\Controller as AdminUserUpdateController;
use App\Cms\Presentation\Http\Controller\Audit\List\Controller as AuditListController;
use App\Cms\Presentation\Http\Controller\Auth\Login\Controller as LoginController;
use App\Cms\Presentation\Http\Controller\Auth\LoginForm\Controller as LoginFormController;
use App\Cms\Presentation\Http\Controller\Auth\Logout\Controller as LogoutController;
use App\Cms\Presentation\Http\Controller\Dashboard\Show\Controller as DashboardShowController;
use App\Cms\Presentation\Http\Controller\Inquiry\Detail\Controller as InquiryDetailController;
use App\Cms\Presentation\Http\Controller\Inquiry\List\Controller as InquiryListController;
use App\Cms\Presentation\Http\Controller\Inquiry\Update\Controller as InquiryUpdateController;
use App\Cms\Presentation\Http\Controller\Media\Delete\Controller as MediaDeleteController;
use App\Cms\Presentation\Http\Controller\Media\List\Controller as MediaListController;
use App\Cms\Presentation\Http\Controller\Media\Store\Controller as MediaStoreController;
use App\Cms\Presentation\Http\Controller\Page\Create\Controller as PageCreateController;
use App\Cms\Presentation\Http\Controller\Page\Edit\Controller as PageEditController;
use App\Cms\Presentation\Http\Controller\Page\List\Controller as PageListController;
use App\Cms\Presentation\Http\Controller\Page\Restore\Controller as PageRestoreController;
use App\Cms\Presentation\Http\Controller\Page\Store\Controller as PageStoreController;
use App\Cms\Presentation\Http\Controller\Page\Update\Controller as PageUpdateController;
use App\Cms\Presentation\Http\Controller\Permission\List\Controller as PermissionListController;
use App\Cms\Presentation\Http\Controller\Role\List\Controller as RoleListController;
use App\Cms\Presentation\Http\Controller\Security\LoginAttempt\Reset\Controller as SecurityLoginAttemptResetController;
use App\Cms\Presentation\Http\Controller\Security\LoginAttempt\Show\Controller as SecurityLoginAttemptShowController;
use App\Cms\Presentation\Http\Controller\Setting\Edit\Controller as SettingEditController;
use App\Cms\Presentation\Http\Controller\Setting\Update\Controller as SettingUpdateController;
use App\Cms\Presentation\Http\Controller\Category\Delete\Controller as CategoryDeleteController;
use App\Cms\Presentation\Http\Controller\Category\List\Controller as CategoryListController;
use App\Cms\Presentation\Http\Controller\Category\Store\Controller as CategoryStoreController;
use App\Cms\Presentation\Http\Controller\Category\Update\Controller as CategoryUpdateController;
use App\Cms\Presentation\Http\Controller\Tag\Delete\Controller as TagDeleteController;
use App\Cms\Presentation\Http\Controller\Tag\List\Controller as TagListController;
use App\Cms\Presentation\Http\Controller\Tag\Store\Controller as TagStoreController;
use App\Cms\Presentation\Http\Controller\Tag\Update\Controller as TagUpdateController;
use App\Cms\Presentation\Http\Controller\Trash\List\Controller as TrashListController;
use App\Cms\Application\Auth\AuthorizeRoute\Value\PermissionCode;
use App\Cms\Presentation\Http\Middleware\RequireCmsPermissionMiddleware;

/**
 * CMS route definitions.
 *
 * Route array shape:
 * - 0: HTTP method. FastRouteへ渡す大文字のHTTPメソッド。
 * - 1: Path pattern. `/admin/articles/{id:\d+}` のようなFastRoute形式のパス。
 * - 2: Controller class. DI containerから解決して呼び出すController FQCN。
 * - 3: Route metadata. 省略可。middleware FQCNをkey、そのmiddleware用の設定値をvalueにする。
 *
 * @var list<array{
 *     0: 'GET'|'POST'|'PUT'|'PATCH'|'DELETE',
 *     1: non-empty-string,
 *     2: class-string,
 *     3?: array<class-string, non-empty-string>
 * }> $routes
 */
$routes = [
    ['GET', '/admin/login', LoginFormController::class],
    ['POST', '/admin/login', LoginController::class],
    ['POST', '/admin/logout', LogoutController::class],
    ['GET', '/admin', DashboardShowController::class],
    ['GET', '/admin/articles', ArticleListController::class],
    ['GET', '/admin/articles/create', ArticleCreateController::class],
    ['GET', '/admin/articles/{id:\d+}', ArticleEditController::class],
    ['POST', '/admin/articles', ArticleStoreController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::ARTICLE_CREATE]],
    ['POST', '/admin/articles/{id:\d+}', ArticleUpdateController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::ARTICLE_UPDATE]],
    ['POST', '/admin/articles/{id:\d+}/delete', ArticleDeleteController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::ARTICLE_DELETE]],
    ['GET', '/admin/articles/{id:\d+}/preview', ArticlePreviewController::class],
    ['GET', '/admin/articles/{id:\d+}/history', ArticleHistoryController::class],
    ['GET', '/admin/articles/{id:\d+}/history/{version:\d+}/preview', ArticleVersionPreviewController::class],
    ['POST', '/admin/articles/{id:\d+}/restore/{version:\d+}', ArticleRestoreController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::ARTICLE_RESTORE]],
    ['GET', '/admin/pages', PageListController::class],
    ['GET', '/admin/pages/create', PageCreateController::class],
    ['GET', '/admin/pages/{id:\d+}', PageEditController::class],
    ['POST', '/admin/pages', PageStoreController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::PAGE_CREATE]],
    ['POST', '/admin/pages/{id:\d+}', PageUpdateController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::PAGE_UPDATE]],
    ['POST', '/admin/pages/{id:\d+}/restore/{version:\d+}', PageRestoreController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::PAGE_UPDATE]],
    ['GET', '/admin/categories', CategoryListController::class],
    ['POST', '/admin/categories', CategoryStoreController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::TAXONOMY_MANAGE]],
    ['POST', '/admin/categories/{id:\d+}', CategoryUpdateController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::TAXONOMY_MANAGE]],
    ['POST', '/admin/categories/{id:\d+}/delete', CategoryDeleteController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::TAXONOMY_MANAGE]],
    ['GET', '/admin/tags', TagListController::class],
    ['POST', '/admin/tags', TagStoreController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::TAXONOMY_MANAGE]],
    ['POST', '/admin/tags/{id:\d+}', TagUpdateController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::TAXONOMY_MANAGE]],
    ['POST', '/admin/tags/{id:\d+}/delete', TagDeleteController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::TAXONOMY_MANAGE]],
    ['GET', '/admin/media', MediaListController::class],
    ['POST', '/admin/media', MediaStoreController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::MEDIA_UPLOAD]],
    ['POST', '/admin/media/{id:\d+}/delete', MediaDeleteController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::MEDIA_DELETE]],
    ['GET', '/admin/inquiries', InquiryListController::class],
    ['GET', '/admin/inquiries/{id:\d+}', InquiryDetailController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::INQUIRY_READ]],
    ['POST', '/admin/inquiries/{id:\d+}', InquiryUpdateController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::INQUIRY_UPDATE]],
    ['GET', '/admin/users', AdminUserListController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::ADMIN_USER_MANAGE]],
    ['POST', '/admin/users', AdminUserCreateController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::ADMIN_USER_MANAGE]],
    ['POST', '/admin/users/{id:\d+}', AdminUserUpdateController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::ADMIN_USER_MANAGE]],
    ['POST', '/admin/users/{id:\d+}/delete', AdminUserDeleteController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::ADMIN_USER_MANAGE]],
    ['GET', '/admin/roles', RoleListController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::ROLE_VIEW]],
    ['GET', '/admin/permissions', PermissionListController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::PERMISSION_VIEW]],
    ['GET', '/admin/security/login-attempts', SecurityLoginAttemptShowController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::SECURITY_MANAGE]],
    ['POST', '/admin/security/login-attempts/reset', SecurityLoginAttemptResetController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::SECURITY_MANAGE]],
    ['GET', '/admin/audit-logs', AuditListController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::AUDIT_VIEW]],
    ['GET', '/admin/settings', SettingEditController::class],
    ['POST', '/admin/settings', SettingUpdateController::class, [RequireCmsPermissionMiddleware::class => PermissionCode::SETTING_UPDATE]],
    ['GET', '/admin/trash', TrashListController::class],
];

return $routes;
