<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\Content\Markdown;
use App\Framework\Database\ConnectionFactory;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;
use App\Front\Application\Article\Search\Query\IArticleQuery as SearchArticleQuery;
use App\Front\Application\Article\Show\Query\IArticleQuery as ShowArticleQuery;
use App\Front\Application\Category\List\Query\ICategoryQuery as ListCategoryQuery;
use App\Front\Application\Category\Show\Query\IArticleQuery as ShowCategoryArticleQuery;
use App\Front\Application\Category\Show\Query\ICategoryQuery as ShowCategoryQuery;
use App\Front\Application\Home\Show\Query\IArticleQuery as HomeArticleQuery;
use App\Front\Application\Home\Show\Query\ICategoryQuery as HomeCategoryQuery;
use App\Front\Application\Page\Show\Query\IPageQuery;
use App\Front\Application\Tag\List\Query\ITagQuery as ListTagQuery;
use App\Front\Application\Tag\Show\Query\IArticleQuery as ShowTagArticleQuery;
use App\Front\Application\Tag\Show\Query\ITagQuery as ShowTagQuery;
use App\Front\Domain\Repository\IInquiryRepository;
use App\Front\Infrastructure\Persistence\PdoArticleQuery;
use App\Front\Infrastructure\Persistence\PdoCategoryQuery;
use App\Front\Infrastructure\Persistence\PdoInquiryRepository;
use App\Front\Infrastructure\Persistence\PdoPageQuery;
use App\Front\Infrastructure\Persistence\PdoTagQuery;

$root = dirname(__DIR__, 3);
$container = new Container();

$container->singleton(\PDO::class, fn (): \PDO => (new ConnectionFactory())->create());
$container->singleton(Renderer::class, fn (): Renderer => new Renderer($root . '/apps/front/templates', $root . '/storage/cache'));
$container->singleton(Session::class, function (): Session {
    $session = new Session();
    $session->start();
    return $session;
});
$container->singleton(Csrf::class, fn (): Csrf => new Csrf());
$container->singleton(Markdown::class, fn (): Markdown => new Markdown());

$container->bind(HomeArticleQuery::class, PdoArticleQuery::class);
$container->bind(ShowArticleQuery::class, PdoArticleQuery::class);
$container->bind(SearchArticleQuery::class, PdoArticleQuery::class);
$container->bind(HomeCategoryQuery::class, PdoCategoryQuery::class);
$container->bind(ListCategoryQuery::class, PdoCategoryQuery::class);
$container->bind(ShowCategoryQuery::class, PdoCategoryQuery::class);
$container->bind(ShowCategoryArticleQuery::class, PdoCategoryQuery::class);
$container->bind(ListTagQuery::class, PdoTagQuery::class);
$container->bind(ShowTagQuery::class, PdoTagQuery::class);
$container->bind(ShowTagArticleQuery::class, PdoTagQuery::class);
$container->bind(IPageQuery::class, PdoPageQuery::class);
$container->bind(IInquiryRepository::class, PdoInquiryRepository::class);

return $container;
