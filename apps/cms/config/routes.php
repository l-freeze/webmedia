<?php

declare(strict_types=1);

use App\Cms\Article\Infrastructure\Http\Controller\ArticleController;
use App\Cms\Auth\Infrastructure\Http\Controller\AuthController;
use App\Cms\Dashboard\Infrastructure\Http\Controller\DashboardController;
use App\Cms\Inquiry\Infrastructure\Http\Controller\InquiryController;
use App\Cms\Media\Infrastructure\Http\Controller\MediaController;
use App\Cms\Page\Infrastructure\Http\Controller\PageController;
use App\Cms\Setting\Infrastructure\Http\Controller\SettingController;
use App\Cms\Taxonomy\Infrastructure\Http\Controller\TaxonomyController;
use App\Cms\Trash\Infrastructure\Http\Controller\TrashController;

return [
    ['GET', '/admin/login', [AuthController::class, 'loginForm']],
    ['POST', '/admin/login', [AuthController::class, 'login']],
    ['POST', '/admin/logout', [AuthController::class, 'logout']],
    ['GET', '/admin', DashboardController::class],
    ['GET', '/admin/articles', [ArticleController::class, 'index']],
    ['GET', '/admin/articles/create', [ArticleController::class, 'create']],
    ['GET', '/admin/articles/{id:\d+}', [ArticleController::class, 'edit']],
    ['POST', '/admin/articles', [ArticleController::class, 'store']],
    ['POST', '/admin/articles/{id:\d+}', [ArticleController::class, 'update']],
    ['GET', '/admin/articles/{id:\d+}/preview', [ArticleController::class, 'preview']],
    ['GET', '/admin/articles/{id:\d+}/history', [ArticleController::class, 'history']],
    ['POST', '/admin/articles/{id:\d+}/restore/{version:\d+}', [ArticleController::class, 'restore']],
    ['GET', '/admin/pages', PageController::class],
    ['POST', '/admin/pages', [PageController::class, 'store']],
    ['GET', '/admin/taxonomy', TaxonomyController::class],
    ['POST', '/admin/taxonomy', [TaxonomyController::class, 'store']],
    ['GET', '/admin/media', MediaController::class],
    ['GET', '/admin/inquiries', InquiryController::class],
    ['GET', '/admin/settings', [SettingController::class, 'edit']],
    ['POST', '/admin/settings', [SettingController::class, 'update']],
    ['GET', '/admin/trash', TrashController::class],
];
