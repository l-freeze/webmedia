<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\Content\Markdown;
use App\Framework\Database\ConnectionFactory;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;
use App\Front\Article\Domain\ArticleQueryService;
use App\Front\Article\Infrastructure\Persistence\PdoArticleQueryService;
use App\Front\Category\Domain\CategoryQueryService;
use App\Front\Category\Infrastructure\Persistence\PdoCategoryQueryService;
use App\Front\Inquiry\Domain\InquiryRepository;
use App\Front\Inquiry\Infrastructure\Persistence\PdoInquiryRepository;
use App\Front\Page\Domain\PageQueryService;
use App\Front\Page\Infrastructure\Persistence\PdoPageQueryService;
use App\Front\Tag\Domain\TagQueryService;
use App\Front\Tag\Infrastructure\Persistence\PdoTagQueryService;
use PDO;

$root = dirname(__DIR__, 3);
$container = new Container();

$container->singleton(PDO::class, fn (): PDO => (new ConnectionFactory())->create());
$container->singleton(Renderer::class, fn (): Renderer => new Renderer($root . '/apps/front/templates', $root . '/storage/cache'));
$container->singleton(Session::class, function (): Session {
    $session = new Session();
    $session->start();
    return $session;
});
$container->singleton(Csrf::class, fn (): Csrf => new Csrf());
$container->singleton(Markdown::class, fn (): Markdown => new Markdown());

$container->bind(ArticleQueryService::class, PdoArticleQueryService::class);
$container->bind(CategoryQueryService::class, PdoCategoryQueryService::class);
$container->bind(TagQueryService::class, PdoTagQueryService::class);
$container->bind(PageQueryService::class, PdoPageQueryService::class);
$container->bind(InquiryRepository::class, PdoInquiryRepository::class);

return $container;
