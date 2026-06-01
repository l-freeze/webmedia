<?php

declare(strict_types=1);

use App\Cms\Application\Article\Edit\Query\IArticleQuery as EditArticleQuery;
use App\Cms\Application\Article\Edit\Query\ICategoryQuery as EditCategoryQuery;
use App\Cms\Application\Article\History\Query\IArticleHistoryQuery;
use App\Cms\Application\Article\List\Query\IArticleQuery as ListArticleQuery;
use App\Cms\Application\Article\Preview\Query\IArticleQuery as PreviewArticleQuery;
use App\Cms\Application\ArticleVersion\Preview\Query\IArticleVersionQuery as PreviewArticleVersionQuery;
use App\Cms\Application\Dashboard\Show\Query\IDashboardQuery;
use App\Cms\Application\Inquiry\List\Query\IInquiryQuery;
use App\Cms\Application\Media\List\Query\IMediaQuery;
use App\Cms\Application\Article\Edit\Query\IMediaQuery as EditMediaQuery;
use App\Cms\Application\Page\Edit\Query\IPageQuery as EditPageQuery;
use App\Cms\Application\Page\History\Query\IPageHistoryQuery;
use App\Cms\Application\Page\List\Query\IPageQuery;
use App\Cms\Application\Setting\Edit\Query\ISettingQuery;
use App\Cms\Application\Taxonomy\List\Query\ICategoryQuery as TaxonomyCategoryQuery;
use App\Cms\Application\Taxonomy\List\Query\ITagQuery as TaxonomyTagQuery;
use App\Cms\Application\Trash\List\Query\ITrashQuery;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Repository\IMediaRepository;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Repository\ISettingRepository;
use App\Cms\Domain\Repository\ITaxonomyRepository;
use App\Cms\Domain\Repository\IUserRepository;
use App\Cms\Infrastructure\Persistence\PdoArticleQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleEditCategoryQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleEditQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleHistoryQuery;
use App\Cms\Infrastructure\Persistence\PdoArticlePreviewQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleRepository;
use App\Cms\Infrastructure\Persistence\PdoArticleVersionPreviewQuery;
use App\Cms\Infrastructure\Persistence\PdoDashboardQuery;
use App\Cms\Infrastructure\Persistence\PdoInquiryQuery;
use App\Cms\Infrastructure\Persistence\PdoMediaQuery;
use App\Cms\Infrastructure\Persistence\PdoMediaRepository;
use App\Cms\Infrastructure\Persistence\PdoPageEditQuery;
use App\Cms\Infrastructure\Persistence\PdoPageHistoryQuery;
use App\Cms\Infrastructure\Persistence\PdoPageQuery;
use App\Cms\Infrastructure\Persistence\PdoPageRepository;
use App\Cms\Infrastructure\Persistence\PdoSettingRepository;
use App\Cms\Infrastructure\Persistence\PdoTaxonomyQuery;
use App\Cms\Infrastructure\Persistence\PdoTaxonomyRepository;
use App\Cms\Infrastructure\Persistence\PdoTrashQuery;
use App\Cms\Infrastructure\Persistence\PdoUserRepository;
use App\Framework\Container\Container;
use App\Framework\Security\Csrf;
use App\Framework\View\Renderer;
use App\Shared\Infrastructure\Http\Middleware\CorsMiddleware;

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
    allowedOrigins: $csv($envString('CMS_CORS_ALLOWED_ORIGINS')),
    allowedMethods: $csv($envString('CMS_CORS_ALLOWED_METHODS', 'GET,POST,OPTIONS')),
    allowedHeaders: $csv($envString('CMS_CORS_ALLOWED_HEADERS', 'Content-Type,X-Requested-With')),
    allowCredentials: filter_var($envString('CMS_CORS_ALLOW_CREDENTIALS', 'false'), FILTER_VALIDATE_BOOL),
    maxAge: (int) $envString('CMS_CORS_MAX_AGE', '600'),
));

$container->singleton(id: Renderer::class, factory: function () use (&$container, $root): Renderer {
    $renderer = new Renderer(templateDirectory: $root . '/apps/cms/templates', cacheDirectory: $root . '/storage/cache');
    $renderer->addGlobalProvider(provider: function () use (&$container): array {
        $csrf = $container->get(id: Csrf::class);
        if (!$csrf instanceof Csrf) {
            throw new \RuntimeException('CSRF service is not registered.');
        }

        return ['csrf' => $csrf->token()];
    });

    return $renderer;
});

$container->bind(abstract: IUserRepository::class, concrete: PdoUserRepository::class);
$container->bind(abstract: IArticleRepository::class, concrete: PdoArticleRepository::class);
$container->bind(abstract: IMediaRepository::class, concrete: PdoMediaRepository::class);
$container->bind(abstract: IPageRepository::class, concrete: PdoPageRepository::class);
$container->bind(abstract: ITaxonomyRepository::class, concrete: PdoTaxonomyRepository::class);
$container->bind(abstract: ISettingRepository::class, concrete: PdoSettingRepository::class);
$container->bind(abstract: ListArticleQuery::class, concrete: PdoArticleQuery::class);
$container->bind(abstract: EditArticleQuery::class, concrete: PdoArticleEditQuery::class);
$container->bind(abstract: EditCategoryQuery::class, concrete: PdoArticleEditCategoryQuery::class);
$container->bind(abstract: PreviewArticleQuery::class, concrete: PdoArticlePreviewQuery::class);
$container->bind(abstract: PreviewArticleVersionQuery::class, concrete: PdoArticleVersionPreviewQuery::class);
$container->bind(abstract: IArticleHistoryQuery::class, concrete: PdoArticleHistoryQuery::class);
$container->bind(abstract: IDashboardQuery::class, concrete: PdoDashboardQuery::class);
$container->bind(abstract: IInquiryQuery::class, concrete: PdoInquiryQuery::class);
$container->bind(abstract: IMediaQuery::class, concrete: PdoMediaQuery::class);
$container->bind(abstract: EditMediaQuery::class, concrete: PdoMediaQuery::class);
$container->bind(abstract: IPageQuery::class, concrete: PdoPageQuery::class);
$container->bind(abstract: EditPageQuery::class, concrete: PdoPageEditQuery::class);
$container->bind(abstract: IPageHistoryQuery::class, concrete: PdoPageHistoryQuery::class);
$container->bind(abstract: ISettingQuery::class, concrete: PdoSettingRepository::class);
$container->bind(abstract: TaxonomyCategoryQuery::class, concrete: PdoTaxonomyQuery::class);
$container->bind(abstract: TaxonomyTagQuery::class, concrete: PdoTaxonomyQuery::class);
$container->bind(abstract: ITrashQuery::class, concrete: PdoTrashQuery::class);

return $container;
