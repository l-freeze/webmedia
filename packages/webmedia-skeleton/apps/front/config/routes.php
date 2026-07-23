<?php

declare(strict_types=1);

use App\Front\Presentation\Http\Controller\Article\Show\Controller as ArticleShowController;
use App\Front\Presentation\Http\Controller\Category\List\Controller as CategoryListController;
use App\Front\Presentation\Http\Controller\Category\Show\Controller as CategoryShowController;
use App\Front\Presentation\Http\Controller\Contact\Create\Controller as ContactCreateController;
use App\Front\Presentation\Http\Controller\Contact\Store\Controller as ContactStoreController;
use App\Front\Presentation\Http\Controller\Contact\Success\Controller as ContactSuccessController;
use App\Front\Presentation\Http\Controller\Home\Show\Controller as HomeShowController;
use App\Front\Presentation\Http\Controller\Page\Show\Controller as PageShowController;
use App\Front\Presentation\Http\Controller\Robots\Controller as RobotsController;
use App\Front\Presentation\Http\Controller\Search\Index\Controller as SearchIndexController;
use App\Front\Presentation\Http\Controller\Sitemap\Controller as SitemapController;
use App\Front\Presentation\Http\Controller\State\Loading\Controller as StateLoadingController;
use App\Front\Presentation\Http\Controller\State\Menu\Controller as StateMenuController;
use App\Front\Presentation\Http\Controller\State\NoArticles\Controller as StateNoArticlesController;
use App\Front\Presentation\Http\Controller\Tag\List\Controller as TagListController;
use App\Front\Presentation\Http\Controller\Tag\Show\Controller as TagShowController;

return [
    ['GET', '/', HomeShowController::class],
    ['GET', '/robots.txt', RobotsController::class],
    ['GET', '/sitemap.xml', SitemapController::class],
    ['GET', '/articles/{slug}', ArticleShowController::class],
    ['GET', '/categories', CategoryListController::class],
    ['GET', '/categories/{slug}', CategoryShowController::class],
    ['GET', '/tags', TagListController::class],
    ['GET', '/tags/{slug}', TagShowController::class],
    ['GET', '/search', SearchIndexController::class],
    ['GET', '/contact', ContactCreateController::class],
    ['POST', '/contact', ContactStoreController::class],
    ['GET', '/contact/success', ContactSuccessController::class],
    ['GET', '/loading', StateLoadingController::class],
    ['GET', '/no-articles', StateNoArticlesController::class],
    ['GET', '/menu', StateMenuController::class],
    ['GET', '/pages/{slug}', PageShowController::class],
];
