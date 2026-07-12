<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\View\TemplateRenderer;
use App\FrontBusiness\Application\Article;
use App\FrontBusiness\Application\Category;
use App\FrontBusiness\Application\Home;
use App\FrontBusiness\Application\Layout;
use App\FrontBusiness\Application\Page;
use App\FrontBusiness\Application\Sitemap;
use App\FrontBusiness\Application\Tag;
use App\FrontBusiness\Domain\Repository\IInquiryRepository;
use App\FrontBusiness\Infrastructure\Configuration\FixedSiteSettingQuery;
use App\FrontBusiness\Infrastructure\Persistence\PdoArticleQuery;
use App\FrontBusiness\Infrastructure\Persistence\PdoCategoryQuery;
use App\FrontBusiness\Infrastructure\Persistence\PdoInquiryRepository;
use App\FrontBusiness\Infrastructure\Persistence\PdoLayoutCategoryQuery;
use App\FrontBusiness\Infrastructure\Persistence\PdoPageLinkQuery;
use App\FrontBusiness\Infrastructure\Persistence\PdoPageQuery;
use App\FrontBusiness\Infrastructure\Persistence\PdoTagQuery;
use App\FrontBusiness\Infrastructure\Persistence\Sitemap\PdoSitemapEntryQuery;
use App\FrontBusiness\Presentation\Seo\CanonicalUrl;
use App\FrontBusiness\Presentation\Sitemap\SitemapXml;
use App\FrontBusiness\Presentation\View\LayoutGlobalProvider;
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
    return array_values(array_filter(array_map(
        static fn (string $item): string => trim($item),
        explode(',', $value),
    ), static fn (string $item): bool => $item !== ''));
};

$container->singleton(id: CorsMiddleware::class, factory: static fn (): CorsMiddleware => new CorsMiddleware(
    allowedOrigins: $csv($envString('FRONT_BUSINESS_CORS_ALLOWED_ORIGINS')),
    allowedMethods: $csv($envString('FRONT_BUSINESS_CORS_ALLOWED_METHODS', 'GET,POST,OPTIONS')),
    allowedHeaders: $csv($envString('FRONT_BUSINESS_CORS_ALLOWED_HEADERS', 'Content-Type,X-Requested-With')),
    allowCredentials: filter_var($envString('FRONT_BUSINESS_CORS_ALLOW_CREDENTIALS', 'false'), FILTER_VALIDATE_BOOL),
    maxAge: (int) $envString('FRONT_BUSINESS_CORS_MAX_AGE', '600'),
));
$container->singleton(id: CanonicalUrl::class, factory: static fn (): CanonicalUrl => new CanonicalUrl(
    baseUrl: $envString('FRONT_BUSINESS_SITE_URL'),
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
        ttlSeconds: max(0, (int) $envString('FRONT_BUSINESS_LAYOUT_CACHE_TTL', '120')),
    );
});

$container->singleton(id: TemplateRenderer::class, factory: function () use (&$container, $root): TemplateRenderer {
    $renderer = new LatteTemplateRenderer(
        templateDirectory: $root . '/apps/front-business/templates',
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
$container->bind(abstract: Layout\Load\Query\ISiteSettingQuery::class, concrete: FixedSiteSettingQuery::class);
$container->bind(abstract: Layout\Load\Query\ICategoryQuery::class, concrete: PdoLayoutCategoryQuery::class);
$container->bind(abstract: IInquiryRepository::class, concrete: PdoInquiryRepository::class);

return $container;
