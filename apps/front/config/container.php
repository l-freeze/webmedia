<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\View\ITemplateRenderer;
use App\Front\Application\Article;
use App\Front\Application\Category;
use App\Front\Application\Home;
use App\Front\Application\Layout;
use App\Front\Application\Page;
use App\Front\Application\Sitemap;
use App\Front\Application\Tag;
use App\Front\Domain\Repository\IInquiryRepository;
use App\Front\Infrastructure\Persistence\PdoArticleQuery;
use App\Front\Infrastructure\Persistence\PdoCategoryQuery;
use App\Front\Infrastructure\Persistence\PdoInquiryRepository;
use App\Front\Infrastructure\Persistence\PdoLayoutCategoryQuery;
use App\Front\Infrastructure\Persistence\PdoPageLinkQuery;
use App\Front\Infrastructure\Persistence\PdoPageQuery;
use App\Front\Infrastructure\Persistence\PdoSiteSettingQuery;
use App\Front\Infrastructure\Persistence\PdoTagQuery;
use App\Front\Infrastructure\Persistence\Sitemap\PdoSitemapEntryQuery;
use App\Front\Presentation\Seo\CanonicalUrl;
use App\Front\Presentation\Sitemap\SitemapXml;
use App\Front\Presentation\View\LayoutGlobalProvider;
use App\Shared\Cors\Infrastructure\Http\CorsMiddleware;
use App\Shared\Security\Infrastructure\Http\SecurityHeadersMiddleware;
use App\Shared\View\Infrastructure\LatteTemplateRenderer;
use Psr\SimpleCache\CacheInterface;

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

$container->singleton(id: SecurityHeadersMiddleware::class, factory: static fn (): SecurityHeadersMiddleware => new SecurityHeadersMiddleware(headers: [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://unpkg.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'",
]));

$envString = static function (string $key, string $default = ''): string {
    $value = $_ENV[$key] ?? $default;

    return is_scalar($value) ? (string) $value : $default;
};

$csv = static function (string $value): array {
    $items = [];
    foreach (explode(',', $value) as $item) {
        $item = trim($item);
        if ($item === '') {
            continue;
        }
        $items[] = $item;
    }

    return $items;
};

$container->singleton(id: CorsMiddleware::class, factory: static fn (): CorsMiddleware => new CorsMiddleware(
    allowedOrigins: $csv($envString('FRONT_CORS_ALLOWED_ORIGINS')),
    allowedMethods: $csv($envString('FRONT_CORS_ALLOWED_METHODS', 'GET,POST,OPTIONS')),
    allowedHeaders: $csv($envString('FRONT_CORS_ALLOWED_HEADERS', 'Content-Type,X-Requested-With')),
    allowCredentials: filter_var($envString('FRONT_CORS_ALLOW_CREDENTIALS', 'false'), FILTER_VALIDATE_BOOL),
    maxAge: (int) $envString('FRONT_CORS_MAX_AGE', '600'),
));
$container->singleton(id: CanonicalUrl::class, factory: static fn (): CanonicalUrl => new CanonicalUrl(
    baseUrl: $envString('FRONT_SITE_URL'),
));
$container->singleton(id: SitemapXml::class, factory: function (Container $container): SitemapXml {
    $canonicalUrl = $container->get(id: CanonicalUrl::class);
    if (!$canonicalUrl instanceof CanonicalUrl) {
        throw new \RuntimeException('Canonical URL service is not registered.');
    }

    return new SitemapXml(canonicalUrl: $canonicalUrl);
});
$container->singleton(id: LayoutGlobalProvider::class, factory: function (Container $container) use ($envString): LayoutGlobalProvider {
    $layout = $container->get(id: Layout\Load\Interactor::class);
    if (!$layout instanceof Layout\Load\Interactor) {
        throw new \RuntimeException('Layout load interactor is not registered.');
    }

    $cache = $container->get(id: CacheInterface::class);
    if (!$cache instanceof CacheInterface) {
        throw new \RuntimeException('Cache service is not registered.');
    }

    return new LayoutGlobalProvider(
        layout: $layout,
        cache: $cache,
        ttlSeconds: max(0, (int) $envString('FRONT_LAYOUT_CACHE_TTL', '120')),
    );
});

$container->singleton(id: ITemplateRenderer::class, factory: function () use (&$container, $root): ITemplateRenderer {
    $renderer = new LatteTemplateRenderer(
        templateDirectory: $root . '/apps/front/templates',
    );
    $renderer->addGlobalProvider(provider: function () use (&$container): array {
        $provider = $container->get(id: LayoutGlobalProvider::class);
        if (!$provider instanceof LayoutGlobalProvider) {
            throw new \RuntimeException('Layout global provider is not registered.');
        }

        return $provider->globals();
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
$container->bind(abstract: Sitemap\Build\Query\ISitemapEntryQuery::class, concrete: PdoSitemapEntryQuery::class);
$container->bind(abstract: Layout\Load\Query\IPageLinkQuery::class, concrete: PdoPageLinkQuery::class);
$container->bind(abstract: Layout\Load\Query\ISiteSettingQuery::class, concrete: PdoSiteSettingQuery::class);
$container->bind(abstract: Layout\Load\Query\ICategoryQuery::class, concrete: PdoLayoutCategoryQuery::class);
$container->bind(abstract: IInquiryRepository::class, concrete: PdoInquiryRepository::class);

return $container;
