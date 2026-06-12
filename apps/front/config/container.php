<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\View\TemplateRenderer;
use App\Front\Application\Article;
use App\Front\Application\Category;
use App\Front\Application\Home;
use App\Front\Application\Layout;
use App\Front\Application\Page;
use App\Front\Application\Tag;
use App\Front\Domain\Repository\IInquiryRepository;
use App\Front\Infrastructure\Persistence\PdoArticleQuery;
use App\Front\Infrastructure\Persistence\PdoCategoryQuery;
use App\Front\Infrastructure\Persistence\PdoInquiryRepository;
use App\Front\Infrastructure\Persistence\PdoPageLinkQuery;
use App\Front\Infrastructure\Persistence\PdoPageQuery;
use App\Front\Infrastructure\Persistence\PdoSiteSettingQuery;
use App\Front\Infrastructure\Persistence\PdoTagQuery;
use App\Shared\Cors\Infrastructure\Http\CorsMiddleware;
use App\Shared\View\Infrastructure\LatteTemplateRenderer;

$root = dirname(__DIR__, 3);
$container = new Container();

$kernelContainer = require $root . '/apps/kernel/config/container.php';
if (!is_callable($kernelContainer)) {
    throw new \RuntimeException('Kernel container configuration is invalid.');
}
$kernelContainer($container);

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

$container->singleton(id: TemplateRenderer::class, factory: function () use (&$container, $root): TemplateRenderer {
    $renderer = new LatteTemplateRenderer(templateDirectory: $root . '/apps/front/templates', cacheDirectory: $root . '/storage/cache');
    $renderer->addGlobalProvider(provider: function () use (&$container): array {
        $pageLinkQuery = $container->get(id: Layout\Query\IPageLinkQuery::class);
        if (!$pageLinkQuery instanceof Layout\Query\IPageLinkQuery) {
            throw new \RuntimeException('Page link query is not registered.');
        }

        $siteSettingQuery = $container->get(id: Layout\Query\ISiteSettingQuery::class);
        if (!$siteSettingQuery instanceof Layout\Query\ISiteSettingQuery) {
            throw new \RuntimeException('Site setting query is not registered.');
        }

        $categoryQuery = $container->get(id: Home\Show\Query\ICategoryQuery::class);
        if (!$categoryQuery instanceof Home\Show\Query\ICategoryQuery) {
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
            'heroImage' => $settings->heroImage,
            'publishedPages' => array_map(fn ($page): array => $page->toArray(), $pages),
            'publishedCategories' => array_map(fn ($category): array => $category->toArray(), $categories),
        ];
    });
    return $renderer;
});

$container->bind(abstract: Home\Show\Query\IArticleQuery::class, concrete: PdoArticleQuery::class);
$container->bind(abstract: Article\Show\Query\IArticleQuery::class, concrete: PdoArticleQuery::class);
$container->bind(abstract: Article\Search\Query\IArticleQuery::class, concrete: PdoArticleQuery::class);
$container->bind(abstract: Home\Show\Query\ICategoryQuery::class, concrete: PdoCategoryQuery::class);
$container->bind(abstract: Category\List\Query\ICategoryQuery::class, concrete: PdoCategoryQuery::class);
$container->bind(abstract: Category\Show\Query\ICategoryQuery::class, concrete: PdoCategoryQuery::class);
$container->bind(abstract: Category\Show\Query\IArticleQuery::class, concrete: PdoCategoryQuery::class);
$container->bind(abstract: Tag\List\Query\ITagQuery::class, concrete: PdoTagQuery::class);
$container->bind(abstract: Tag\Show\Query\ITagQuery::class, concrete: PdoTagQuery::class);
$container->bind(abstract: Tag\Show\Query\IArticleQuery::class, concrete: PdoTagQuery::class);
$container->bind(abstract: Page\Show\Query\IPageQuery::class, concrete: PdoPageQuery::class);
$container->bind(abstract: Layout\Query\IPageLinkQuery::class, concrete: PdoPageLinkQuery::class);
$container->bind(abstract: Layout\Query\ISiteSettingQuery::class, concrete: PdoSiteSettingQuery::class);
$container->bind(abstract: IInquiryRepository::class, concrete: PdoInquiryRepository::class);

return $container;
