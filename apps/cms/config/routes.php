<?php

declare(strict_types=1);

use App\Cms\Infrastructure\Http\Controller\ArticleController;
use App\Cms\Auth\Infrastructure\Http\Controller\AuthController;
use App\Cms\Infrastructure\Http\Controller\DashboardController;
use App\Cms\Infrastructure\Http\Controller\InquiryController;
use App\Cms\Infrastructure\Http\Controller\MediaController;
use App\Cms\Infrastructure\Http\Controller\PageController;
use App\Cms\Infrastructure\Http\Controller\SettingController;
use App\Cms\Infrastructure\Http\Controller\TaxonomyController;
use App\Cms\Infrastructure\Http\Controller\TrashController;

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
    ['GET', '/admin/articles/{id:\d+}/history/{version:\d+}/preview', [ArticleController::class, 'previewArticleVersion']],
    ['POST', '/admin/articles/{id:\d+}/restore/{version:\d+}', [ArticleController::class, 'restore']],
    ['GET', '/admin/pages', [PageController::class, 'index']],
    ['GET', '/admin/pages/create', [PageController::class, 'create']],
    ['GET', '/admin/pages/{id:\d+}', [PageController::class, 'edit']],
    ['POST', '/admin/pages', [PageController::class, 'store']],
    ['POST', '/admin/pages/{id:\d+}', [PageController::class, 'update']],
    ['GET', '/admin/taxonomy', TaxonomyController::class],
    ['POST', '/admin/taxonomy', [TaxonomyController::class, 'store']],
    ['GET', '/admin/media', MediaController::class],
    ['GET', '/admin/inquiries', InquiryController::class],
    ['GET', '/admin/settings', [SettingController::class, 'edit']],
    ['POST', '/admin/settings', [SettingController::class, 'update']],
    ['GET', '/admin/trash', TrashController::class],
];
