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
use App\Cms\Application\Auth\Authorization\Value\PermissionCode;

/**
 * CMS route definitions.
 *
 * Route array shape:
 * - 0: HTTP method. FastRouteへ渡す大文字のHTTPメソッド。
 * - 1: Path pattern. `/admin/articles/{id:\d+}` のようなFastRoute形式のパス。
 * - 2: Controller class. DI containerから解決して呼び出すController FQCN。
 * - 3: Route metadata. 省略可。`requires_permission` はこのrouteへ入るために必要なPermission名。
 *
 * @var list<array{
 *     0: 'GET'|'POST'|'PUT'|'PATCH'|'DELETE',
 *     1: non-empty-string,
 *     2: class-string,
 *     3?: array{requires_permission?: non-empty-string}
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
    ['POST', '/admin/articles', ArticleStoreController::class, ['requires_permission' => PermissionCode::ARTICLE_CREATE]],
    ['POST', '/admin/articles/{id:\d+}', ArticleUpdateController::class, ['requires_permission' => PermissionCode::ARTICLE_UPDATE]],
    ['POST', '/admin/articles/{id:\d+}/delete', ArticleDeleteController::class, ['requires_permission' => PermissionCode::ARTICLE_DELETE]],
    ['GET', '/admin/articles/{id:\d+}/preview', ArticlePreviewController::class],
    ['GET', '/admin/articles/{id:\d+}/history', ArticleHistoryController::class],
    ['GET', '/admin/articles/{id:\d+}/history/{version:\d+}/preview', ArticleVersionPreviewController::class],
    ['POST', '/admin/articles/{id:\d+}/restore/{version:\d+}', ArticleRestoreController::class, ['requires_permission' => PermissionCode::ARTICLE_RESTORE]],
    ['GET', '/admin/pages', PageListController::class],
    ['GET', '/admin/pages/create', PageCreateController::class],
    ['GET', '/admin/pages/{id:\d+}', PageEditController::class],
    ['POST', '/admin/pages', PageStoreController::class, ['requires_permission' => PermissionCode::PAGE_CREATE]],
    ['POST', '/admin/pages/{id:\d+}', PageUpdateController::class, ['requires_permission' => PermissionCode::PAGE_UPDATE]],
    ['POST', '/admin/pages/{id:\d+}/restore/{version:\d+}', PageRestoreController::class, ['requires_permission' => PermissionCode::PAGE_UPDATE]],
    ['GET', '/admin/categories', CategoryListController::class],
    ['POST', '/admin/categories', CategoryStoreController::class, ['requires_permission' => PermissionCode::TAXONOMY_MANAGE]],
    ['POST', '/admin/categories/{id:\d+}', CategoryUpdateController::class, ['requires_permission' => PermissionCode::TAXONOMY_MANAGE]],
    ['POST', '/admin/categories/{id:\d+}/delete', CategoryDeleteController::class, ['requires_permission' => PermissionCode::TAXONOMY_MANAGE]],
    ['GET', '/admin/tags', TagListController::class],
    ['POST', '/admin/tags', TagStoreController::class, ['requires_permission' => PermissionCode::TAXONOMY_MANAGE]],
    ['POST', '/admin/tags/{id:\d+}', TagUpdateController::class, ['requires_permission' => PermissionCode::TAXONOMY_MANAGE]],
    ['POST', '/admin/tags/{id:\d+}/delete', TagDeleteController::class, ['requires_permission' => PermissionCode::TAXONOMY_MANAGE]],
    ['GET', '/admin/media', MediaListController::class],
    ['POST', '/admin/media', MediaStoreController::class, ['requires_permission' => PermissionCode::MEDIA_UPLOAD]],
    ['POST', '/admin/media/{id:\d+}/delete', MediaDeleteController::class, ['requires_permission' => PermissionCode::MEDIA_DELETE]],
    ['GET', '/admin/inquiries', InquiryListController::class],
    ['GET', '/admin/inquiries/{id:\d+}', InquiryDetailController::class, ['requires_permission' => PermissionCode::INQUIRY_READ]],
    ['POST', '/admin/inquiries/{id:\d+}', InquiryUpdateController::class, ['requires_permission' => PermissionCode::INQUIRY_UPDATE]],
    ['GET', '/admin/users', AdminUserListController::class, ['requires_permission' => PermissionCode::ADMIN_USER_MANAGE]],
    ['POST', '/admin/users', AdminUserCreateController::class, ['requires_permission' => PermissionCode::ADMIN_USER_MANAGE]],
    ['POST', '/admin/users/{id:\d+}', AdminUserUpdateController::class, ['requires_permission' => PermissionCode::ADMIN_USER_MANAGE]],
    ['POST', '/admin/users/{id:\d+}/delete', AdminUserDeleteController::class, ['requires_permission' => PermissionCode::ADMIN_USER_MANAGE]],
    ['GET', '/admin/roles', RoleListController::class, ['requires_permission' => PermissionCode::ROLE_VIEW]],
    ['GET', '/admin/permissions', PermissionListController::class, ['requires_permission' => PermissionCode::PERMISSION_VIEW]],
    ['GET', '/admin/security/login-attempts', SecurityLoginAttemptShowController::class, ['requires_permission' => PermissionCode::SECURITY_MANAGE]],
    ['POST', '/admin/security/login-attempts/reset', SecurityLoginAttemptResetController::class, ['requires_permission' => PermissionCode::SECURITY_MANAGE]],
    ['GET', '/admin/audit-logs', AuditListController::class, ['requires_permission' => PermissionCode::AUDIT_VIEW]],
    ['GET', '/admin/settings', SettingEditController::class],
    ['POST', '/admin/settings', SettingUpdateController::class, ['requires_permission' => PermissionCode::SETTING_UPDATE]],
    ['GET', '/admin/trash', TrashListController::class],
];

return $routes;
