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
use App\Cms\Application\Page\Edit\Query\IPageQuery as EditPageQuery;
use App\Cms\Application\Page\History\Query\IPageHistoryQuery;
use App\Cms\Application\Page\List\Query\IPageQuery;
use App\Cms\Application\Setting\Edit\Query\ISettingQuery;
use App\Cms\Application\Taxonomy\List\Query\ICategoryQuery as TaxonomyCategoryQuery;
use App\Cms\Application\Taxonomy\List\Query\ITagQuery as TaxonomyTagQuery;
use App\Cms\Application\Trash\List\Query\ITrashQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleRepository;
use App\Cms\Auth\Domain\IUserRepository;
use App\Cms\Auth\Infrastructure\Persistence\PdoUserRepository;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Repository\ISettingRepository;
use App\Cms\Domain\Repository\ITaxonomyRepository;
use App\Cms\Infrastructure\Persistence\PdoArticleQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleEditCategoryQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleEditQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleHistoryQuery;
use App\Cms\Infrastructure\Persistence\PdoArticlePreviewQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleVersionPreviewQuery;
use App\Cms\Infrastructure\Persistence\PdoDashboardQuery;
use App\Cms\Infrastructure\Persistence\PdoInquiryQuery;
use App\Cms\Infrastructure\Persistence\PdoMediaQuery;
use App\Cms\Infrastructure\Persistence\PdoPageEditQuery;
use App\Cms\Infrastructure\Persistence\PdoPageHistoryQuery;
use App\Cms\Infrastructure\Persistence\PdoPageQuery;
use App\Cms\Infrastructure\Persistence\PdoPageRepository;
use App\Cms\Infrastructure\Persistence\PdoSettingRepository;
use App\Cms\Infrastructure\Persistence\PdoTaxonomyQuery;
use App\Cms\Infrastructure\Persistence\PdoTaxonomyRepository;
use App\Cms\Infrastructure\Persistence\PdoTrashQuery;
use App\Framework\Container\Container;
use App\Framework\Content\Markdown;
use App\Framework\Database\ConnectionFactory;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

$root = dirname(__DIR__, 3);
$container = new Container();

$container->singleton(\PDO::class, fn (): \PDO => (new ConnectionFactory())->create());
$container->singleton(Renderer::class, fn (): Renderer => new Renderer($root . '/apps/cms/templates', $root . '/storage/cache'));
$container->singleton(Session::class, function (): Session {
    $session = new Session();
    $session->start();
    return $session;
});
$container->singleton(Csrf::class, fn (): Csrf => new Csrf());
$container->singleton(Markdown::class, fn (): Markdown => new Markdown());

$container->bind(IUserRepository::class, PdoUserRepository::class);
$container->bind(IArticleRepository::class, PdoArticleRepository::class);
$container->bind(IPageRepository::class, PdoPageRepository::class);
$container->bind(ITaxonomyRepository::class, PdoTaxonomyRepository::class);
$container->bind(ISettingRepository::class, PdoSettingRepository::class);
$container->bind(ListArticleQuery::class, PdoArticleQuery::class);
$container->bind(EditArticleQuery::class, PdoArticleEditQuery::class);
$container->bind(EditCategoryQuery::class, PdoArticleEditCategoryQuery::class);
$container->bind(PreviewArticleQuery::class, PdoArticlePreviewQuery::class);
$container->bind(PreviewArticleVersionQuery::class, PdoArticleVersionPreviewQuery::class);
$container->bind(IArticleHistoryQuery::class, PdoArticleHistoryQuery::class);
$container->bind(IDashboardQuery::class, PdoDashboardQuery::class);
$container->bind(IInquiryQuery::class, PdoInquiryQuery::class);
$container->bind(IMediaQuery::class, PdoMediaQuery::class);
$container->bind(IPageQuery::class, PdoPageQuery::class);
$container->bind(EditPageQuery::class, PdoPageEditQuery::class);
$container->bind(IPageHistoryQuery::class, PdoPageHistoryQuery::class);
$container->bind(ISettingQuery::class, PdoSettingRepository::class);
$container->bind(TaxonomyCategoryQuery::class, PdoTaxonomyQuery::class);
$container->bind(TaxonomyTagQuery::class, PdoTaxonomyQuery::class);
$container->bind(ITrashQuery::class, PdoTrashQuery::class);

return $container;
