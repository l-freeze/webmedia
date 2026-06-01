<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\View\Renderer;
use App\Front\Application\Article\Search\Query\IArticleQuery as SearchArticleQuery;
use App\Front\Application\Article\Show\Query\IArticleQuery as ShowArticleQuery;
use App\Front\Application\Category\List\Query\ICategoryQuery as ListCategoryQuery;
use App\Front\Application\Category\Show\Query\IArticleQuery as ShowCategoryArticleQuery;
use App\Front\Application\Category\Show\Query\ICategoryQuery as ShowCategoryQuery;
use App\Front\Application\Home\Show\Query\IArticleQuery as HomeArticleQuery;
use App\Front\Application\Home\Show\Query\ICategoryQuery as HomeCategoryQuery;
use App\Front\Application\Layout\Query\IPageLinkQuery;
use App\Front\Application\Layout\Query\ISiteSettingQuery;
use App\Front\Application\Page\Show\Query\IPageQuery;
use App\Front\Application\Tag\List\Query\ITagQuery as ListTagQuery;
use App\Front\Application\Tag\Show\Query\IArticleQuery as ShowTagArticleQuery;
use App\Front\Application\Tag\Show\Query\ITagQuery as ShowTagQuery;
use App\Front\Domain\Repository\IInquiryRepository;
use App\Front\Infrastructure\Persistence\PdoArticleQuery;
use App\Front\Infrastructure\Persistence\PdoCategoryQuery;
use App\Front\Infrastructure\Persistence\PdoInquiryRepository;
use App\Front\Infrastructure\Persistence\PdoPageLinkQuery;
use App\Front\Infrastructure\Persistence\PdoPageQuery;
use App\Front\Infrastructure\Persistence\PdoSiteSettingQuery;
use App\Front\Infrastructure\Persistence\PdoTagQuery;
use App\Shared\Cors\Infrastructure\Http\CorsMiddleware;

$root = dirname(__DIR__, 3);
$container = new Container();

$sharedContainer = require $root . '/apps/shared/config/container.php';
if (!is_callable($sharedContainer)) {
    throw new \RuntimeException('Shared container configuration is invalid.');
}
$sharedContainer($container, $root);

$envString = static function (string $key, string $default = ''): string {
    $value = $_ENV[$key] ?? $default;

    return is_scalar($value) ? (string) $value : $default;
};

$csv = static function (string $value): array {
    return array_values(array_filter(array_map(
        static fn (string $item): string => trim($item),
        explode(',', $value),
    ), static fn (string $item): bool => $item !== ''));
};

$container->singleton(id: CorsMiddleware::class, factory: static fn (): CorsMiddleware => new CorsMiddleware(
    allowedOrigins: $csv($envString('FRONT_CORS_ALLOWED_ORIGINS')),
    allowedMethods: $csv($envString('FRONT_CORS_ALLOWED_METHODS', 'GET,POST,OPTIONS')),
    allowedHeaders: $csv($envString('FRONT_CORS_ALLOWED_HEADERS', 'Content-Type,X-Requested-With')),
    allowCredentials: filter_var($envString('FRONT_CORS_ALLOW_CREDENTIALS', 'false'), FILTER_VALIDATE_BOOL),
    maxAge: (int) $envString('FRONT_CORS_MAX_AGE', '600'),
));

$container->singleton(id: Renderer::class, factory: function () use (&$container, $root): Renderer {
    $renderer = new Renderer(templateDirectory: $root . '/apps/front/templates', cacheDirectory: $root . '/storage/cache');
    $renderer->addGlobalProvider(provider: function () use (&$container): array {
        $pageLinkQuery = $container->get(id: IPageLinkQuery::class);
        if (!$pageLinkQuery instanceof IPageLinkQuery) {
            throw new \RuntimeException('Page link query is not registered.');
        }

        $siteSettingQuery = $container->get(id: ISiteSettingQuery::class);
        if (!$siteSettingQuery instanceof ISiteSettingQuery) {
            throw new \RuntimeException('Site setting query is not registered.');
        }

        $categoryQuery = $container->get(id: HomeCategoryQuery::class);
        if (!$categoryQuery instanceof HomeCategoryQuery) {
            throw new \RuntimeException('Home category query is not registered.');
        }

        $pages = $pageLinkQuery->publishedPageLinks();
        $settings = $siteSettingQuery->current();
        $categories = $categoryQuery->all();

        return [
            'siteName' => $settings->siteName,
            'description' => $settings->siteDescription,
            'frontTheme' => $settings->frontTheme,
            'searchEnabled' => $settings->searchEnabled,
            'publishedPages' => array_map(fn ($page): array => $page->toArray(), $pages),
            'publishedCategories' => array_map(fn ($category): array => $category->toArray(), $categories),
        ];
    });
    return $renderer;
});

$container->bind(abstract: HomeArticleQuery::class, concrete: PdoArticleQuery::class);
$container->bind(abstract: ShowArticleQuery::class, concrete: PdoArticleQuery::class);
$container->bind(abstract: SearchArticleQuery::class, concrete: PdoArticleQuery::class);
$container->bind(abstract: HomeCategoryQuery::class, concrete: PdoCategoryQuery::class);
$container->bind(abstract: ListCategoryQuery::class, concrete: PdoCategoryQuery::class);
$container->bind(abstract: ShowCategoryQuery::class, concrete: PdoCategoryQuery::class);
$container->bind(abstract: ShowCategoryArticleQuery::class, concrete: PdoCategoryQuery::class);
$container->bind(abstract: ListTagQuery::class, concrete: PdoTagQuery::class);
$container->bind(abstract: ShowTagQuery::class, concrete: PdoTagQuery::class);
$container->bind(abstract: ShowTagArticleQuery::class, concrete: PdoTagQuery::class);
$container->bind(abstract: IPageQuery::class, concrete: PdoPageQuery::class);
$container->bind(abstract: IPageLinkQuery::class, concrete: PdoPageLinkQuery::class);
$container->bind(abstract: ISiteSettingQuery::class, concrete: PdoSiteSettingQuery::class);
$container->bind(abstract: IInquiryRepository::class, concrete: PdoInquiryRepository::class);

return $container;
