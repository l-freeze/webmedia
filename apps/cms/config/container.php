<?php

declare(strict_types=1);

use App\Cms\Article\Domain\ArticleRepository;
use App\Cms\Article\Infrastructure\Persistence\PdoArticleRepository;
use App\Cms\Auth\Domain\UserRepository;
use App\Cms\Auth\Infrastructure\Persistence\PdoUserRepository;
use App\Cms\Dashboard\Infrastructure\Persistence\PdoDashboardQueryService;
use App\Cms\Dashboard\Query\ShowDashboard\DashboardQueryService;
use App\Cms\Inquiry\Infrastructure\Persistence\PdoInquiryQueryService;
use App\Cms\Inquiry\Query\ListInquiries\InquiryQueryService;
use App\Cms\Media\Infrastructure\Persistence\PdoMediaQueryService;
use App\Cms\Media\Query\ListMedia\MediaQueryService;
use App\Cms\Page\Domain\PageRepository;
use App\Cms\Page\Infrastructure\Persistence\PdoPageRepository;
use App\Cms\Setting\Infrastructure\Persistence\PdoSettingRepository;
use App\Cms\Setting\Query\GetSettings\SettingRepository;
use App\Cms\Taxonomy\Domain\TaxonomyRepository;
use App\Cms\Taxonomy\Infrastructure\Persistence\PdoTaxonomyRepository;
use App\Cms\Trash\Infrastructure\Persistence\PdoTrashQueryService;
use App\Cms\Trash\Query\ListTrash\TrashQueryService;
use App\Framework\Container\Container;
use App\Framework\Content\Markdown;
use App\Framework\Database\ConnectionFactory;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;
use PDO;

$root = dirname(__DIR__, 3);
$container = new Container();

$container->singleton(PDO::class, fn (): PDO => (new ConnectionFactory())->create());
$container->singleton(Renderer::class, fn (): Renderer => new Renderer($root . '/apps/cms/templates', $root . '/storage/cache'));
$container->singleton(Session::class, function (): Session {
    $session = new Session();
    $session->start();
    return $session;
});
$container->singleton(Csrf::class, fn (): Csrf => new Csrf());
$container->singleton(Markdown::class, fn (): Markdown => new Markdown());

$container->bind(UserRepository::class, PdoUserRepository::class);
$container->bind(ArticleRepository::class, PdoArticleRepository::class);
$container->bind(PageRepository::class, PdoPageRepository::class);
$container->bind(TaxonomyRepository::class, PdoTaxonomyRepository::class);
$container->bind(DashboardQueryService::class, PdoDashboardQueryService::class);
$container->bind(MediaQueryService::class, PdoMediaQueryService::class);
$container->bind(InquiryQueryService::class, PdoInquiryQueryService::class);
$container->bind(SettingRepository::class, PdoSettingRepository::class);
$container->bind(TrashQueryService::class, PdoTrashQueryService::class);

return $container;
