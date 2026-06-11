<?php

declare(strict_types=1);

use App\Cms\Infrastructure\Http\Controller\Article\Create\Controller as ArticleCreateController;
use App\Cms\Infrastructure\Http\Controller\Article\Delete\Controller as ArticleDeleteController;
use App\Cms\Infrastructure\Http\Controller\Article\Edit\Controller as ArticleEditController;
use App\Cms\Infrastructure\Http\Controller\Article\History\Controller as ArticleHistoryController;
use App\Cms\Infrastructure\Http\Controller\Article\List\Controller as ArticleListController;
use App\Cms\Infrastructure\Http\Controller\Article\Preview\Controller as ArticlePreviewController;
use App\Cms\Infrastructure\Http\Controller\Article\Restore\Controller as ArticleRestoreController;
use App\Cms\Infrastructure\Http\Controller\Article\Store\Controller as ArticleStoreController;
use App\Cms\Infrastructure\Http\Controller\Article\Update\Controller as ArticleUpdateController;
use App\Cms\Infrastructure\Http\Controller\Article\VersionPreview\Controller as ArticleVersionPreviewController;
use App\Cms\Infrastructure\Http\Controller\Auth\Login\Controller as LoginController;
use App\Cms\Infrastructure\Http\Controller\Auth\LoginForm\Controller as LoginFormController;
use App\Cms\Infrastructure\Http\Controller\Auth\Logout\Controller as LogoutController;
use App\Cms\Infrastructure\Http\Controller\Dashboard\Show\Controller as DashboardShowController;
use App\Cms\Infrastructure\Http\Controller\Inquiry\List\Controller as InquiryListController;
use App\Cms\Infrastructure\Http\Controller\Media\Delete\Controller as MediaDeleteController;
use App\Cms\Infrastructure\Http\Controller\Media\List\Controller as MediaListController;
use App\Cms\Infrastructure\Http\Controller\Media\Store\Controller as MediaStoreController;
use App\Cms\Infrastructure\Http\Controller\Page\Create\Controller as PageCreateController;
use App\Cms\Infrastructure\Http\Controller\Page\Edit\Controller as PageEditController;
use App\Cms\Infrastructure\Http\Controller\Page\List\Controller as PageListController;
use App\Cms\Infrastructure\Http\Controller\Page\Store\Controller as PageStoreController;
use App\Cms\Infrastructure\Http\Controller\Page\Update\Controller as PageUpdateController;
use App\Cms\Infrastructure\Http\Controller\Setting\Edit\Controller as SettingEditController;
use App\Cms\Infrastructure\Http\Controller\Setting\Update\Controller as SettingUpdateController;
use App\Cms\Infrastructure\Http\Controller\Category\Delete\Controller as CategoryDeleteController;
use App\Cms\Infrastructure\Http\Controller\Category\List\Controller as CategoryListController;
use App\Cms\Infrastructure\Http\Controller\Category\Store\Controller as CategoryStoreController;
use App\Cms\Infrastructure\Http\Controller\Category\Update\Controller as CategoryUpdateController;
use App\Cms\Infrastructure\Http\Controller\Tag\Delete\Controller as TagDeleteController;
use App\Cms\Infrastructure\Http\Controller\Tag\List\Controller as TagListController;
use App\Cms\Infrastructure\Http\Controller\Tag\Store\Controller as TagStoreController;
use App\Cms\Infrastructure\Http\Controller\Tag\Update\Controller as TagUpdateController;
use App\Cms\Infrastructure\Http\Controller\Trash\List\Controller as TrashListController;

return [
    ['GET', '/admin/login', LoginFormController::class],
    ['POST', '/admin/login', LoginController::class],
    ['POST', '/admin/logout', LogoutController::class],
    ['GET', '/admin', DashboardShowController::class],
    ['GET', '/admin/articles', ArticleListController::class],
    ['GET', '/admin/articles/create', ArticleCreateController::class],
    ['GET', '/admin/articles/{id:\d+}', ArticleEditController::class],
    ['POST', '/admin/articles', ArticleStoreController::class],
    ['POST', '/admin/articles/{id:\d+}', ArticleUpdateController::class],
    ['POST', '/admin/articles/{id:\d+}/delete', ArticleDeleteController::class],
    ['GET', '/admin/articles/{id:\d+}/preview', ArticlePreviewController::class],
    ['GET', '/admin/articles/{id:\d+}/history', ArticleHistoryController::class],
    ['GET', '/admin/articles/{id:\d+}/history/{version:\d+}/preview', ArticleVersionPreviewController::class],
    ['POST', '/admin/articles/{id:\d+}/restore/{version:\d+}', ArticleRestoreController::class],
    ['GET', '/admin/pages', PageListController::class],
    ['GET', '/admin/pages/create', PageCreateController::class],
    ['GET', '/admin/pages/{id:\d+}', PageEditController::class],
    ['POST', '/admin/pages', PageStoreController::class],
    ['POST', '/admin/pages/{id:\d+}', PageUpdateController::class],
    ['GET', '/admin/categories', CategoryListController::class],
    ['POST', '/admin/categories', CategoryStoreController::class],
    ['POST', '/admin/categories/{id:\d+}', CategoryUpdateController::class],
    ['POST', '/admin/categories/{id:\d+}/delete', CategoryDeleteController::class],
    ['GET', '/admin/tags', TagListController::class],
    ['POST', '/admin/tags', TagStoreController::class],
    ['POST', '/admin/tags/{id:\d+}', TagUpdateController::class],
    ['POST', '/admin/tags/{id:\d+}/delete', TagDeleteController::class],
    ['GET', '/admin/media', MediaListController::class],
    ['POST', '/admin/media', MediaStoreController::class],
    ['POST', '/admin/media/{id:\d+}/delete', MediaDeleteController::class],
    ['GET', '/admin/inquiries', InquiryListController::class],
    ['GET', '/admin/settings', SettingEditController::class],
    ['POST', '/admin/settings', SettingUpdateController::class],
    ['GET', '/admin/trash', TrashListController::class],
];
