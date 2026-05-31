<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\Content\Markdown;
use App\Framework\Database\ConnectionFactory;
use App\Framework\Logging\AuditLoggerInterface;
use App\Framework\Logging\LoggerInterface;
use App\Framework\Logging\PlaceholderAuditLogger;
use App\Framework\Logging\PlaceholderLogger;
use App\Framework\Security\Csrf;
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

$root = dirname(__DIR__, 3);
$container = new Container();

$container->singleton(id: \PDO::class, factory: fn (): \PDO => (new ConnectionFactory())->create());
$container->singleton(id: Renderer::class, factory: function () use (&$container, $root): Renderer {
    $renderer = new Renderer(templateDirectory: $root . '/apps/front/templates', cacheDirectory: $root . '/storage/cache');
    $renderer->addGlobalProvider(provider: function () use (&$container): array {
        $pages = $container->get(id: IPageLinkQuery::class)->publishedPageLinks();
        $settings = $container->get(id: ISiteSettingQuery::class)->current();
        $categories = $container->get(id: HomeCategoryQuery::class)->all();
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
$container->singleton(id: Csrf::class, factory: function (Container $container): Csrf {
    $session = $container->get(id: \App\Framework\Security\Session::class);
    if (!$session instanceof \App\Framework\Security\Session) {
        throw new \RuntimeException('Session service is not registered.');
    }

    return new Csrf(session: $session);
});
$container->singleton(id: Markdown::class, factory: fn (): Markdown => new Markdown());
$container->singleton(id: LoggerInterface::class, factory: fn (): LoggerInterface => new PlaceholderLogger());
$container->singleton(id: AuditLoggerInterface::class, factory: fn (): AuditLoggerInterface => new PlaceholderAuditLogger());

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
