<?php

declare(strict_types=1);

use App\FrontBusiness\Presentation\Http\Controller\Article\Show\Controller as ArticleShowController;
use App\FrontBusiness\Presentation\Http\Controller\Category\List\Controller as CategoryListController;
use App\FrontBusiness\Presentation\Http\Controller\Category\Show\Controller as CategoryShowController;
use App\FrontBusiness\Presentation\Http\Controller\Contact\Create\Controller as ContactCreateController;
use App\FrontBusiness\Presentation\Http\Controller\Contact\Store\Controller as ContactStoreController;
use App\FrontBusiness\Presentation\Http\Controller\Contact\Success\Controller as ContactSuccessController;
use App\FrontBusiness\Presentation\Http\Controller\Home\Show\Controller as HomeShowController;
use App\FrontBusiness\Presentation\Http\Controller\Page\Show\Controller as PageShowController;
use App\FrontBusiness\Presentation\Http\Controller\Robots\Controller as RobotsController;
use App\FrontBusiness\Presentation\Http\Controller\Search\Index\Controller as SearchIndexController;
use App\FrontBusiness\Presentation\Http\Controller\Sitemap\Controller as SitemapController;
use App\FrontBusiness\Presentation\Http\Controller\State\Loading\Controller as StateLoadingController;
use App\FrontBusiness\Presentation\Http\Controller\State\Menu\Controller as StateMenuController;
use App\FrontBusiness\Presentation\Http\Controller\State\NoArticles\Controller as StateNoArticlesController;
use App\FrontBusiness\Presentation\Http\Controller\Tag\List\Controller as TagListController;
use App\FrontBusiness\Presentation\Http\Controller\Tag\Show\Controller as TagShowController;
use App\FrontBusiness\Presentation\BasePath;

$routes = [
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

return array_map(static function (array $route): array {
    $route[1] = BasePath::path(path: $route[1]);

    return $route;
}, $routes);
