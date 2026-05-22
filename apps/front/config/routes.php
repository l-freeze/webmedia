<?php

declare(strict_types=1);

use App\Front\Article\Infrastructure\Http\Controller\ArticleController;
use App\Front\Article\Infrastructure\Http\Controller\HomeController;
use App\Front\Article\Infrastructure\Http\Controller\SearchController;
use App\Front\Article\Infrastructure\Http\Controller\StateController;
use App\Front\Category\Infrastructure\Http\Controller\CategoryController;
use App\Front\Inquiry\Infrastructure\Http\Controller\ContactController;
use App\Front\Page\Infrastructure\Http\Controller\PageController;
use App\Front\Tag\Infrastructure\Http\Controller\TagController;

return [
    ['GET', '/', HomeController::class],
    ['GET', '/articles/{slug}', ArticleController::class],
    ['GET', '/categories', [CategoryController::class, 'index']],
    ['GET', '/categories/{slug}', [CategoryController::class, 'show']],
    ['GET', '/tags', [TagController::class, 'index']],
    ['GET', '/tags/{slug}', [TagController::class, 'show']],
    ['GET', '/search', SearchController::class],
    ['GET', '/contact', [ContactController::class, 'create']],
    ['POST', '/contact', [ContactController::class, 'store']],
    ['GET', '/contact/success', [ContactController::class, 'success']],
    ['GET', '/loading', [StateController::class, 'loading']],
    ['GET', '/no-articles', [StateController::class, 'noArticles']],
    ['GET', '/menu', [StateController::class, 'menu']],
    ['GET', '/pages/{slug}', PageController::class],
];
