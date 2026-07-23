<?php

declare(strict_types=1);

use App\Cms\Application\Article;
use App\Cms\Application\ArticleVersion;
use App\Cms\Application\AdminUser;
use App\Cms\Application\Audit;
use App\Cms\Application\Auth;
use App\Cms\Application\Category;
use App\Cms\Application\Dashboard;
use App\Cms\Application\Inquiry;
use App\Cms\Application\Media;
use App\Cms\Application\Page;
use App\Cms\Application\Permission;
use App\Cms\Application\Role;
use App\Cms\Application\Setting;
use App\Cms\Application\Tag;
use App\Cms\Application\Trash;
use App\Cms\Domain\Repository\IAdminUserRepository;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Repository\ICategoryRepository;
use App\Cms\Domain\Repository\IInquiryRepository;
use App\Cms\Domain\Repository\ICmsLoginAttemptResetRepository;
use App\Cms\Domain\Repository\ILoginAttemptRepository;
use App\Cms\Domain\Repository\IMediaRepository;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Repository\ISettingRepository;
use App\Cms\Domain\Repository\ITagRepository;
use App\Cms\Domain\Repository\IUserRepository;
use App\Cms\Infrastructure\Persistence\PdoArticleQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleEditCategoryQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleEditImageQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleEditQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleHistoryQuery;
use App\Cms\Infrastructure\Persistence\PdoArticlePreviewQuery;
use App\Cms\Infrastructure\Persistence\PdoArticleRepository;
use App\Cms\Infrastructure\Persistence\PdoArticleVersionPreviewQuery;
use App\Cms\Infrastructure\Persistence\PdoAdminUserRepository;
use App\Cms\Infrastructure\Persistence\PdoCurrentCmsUserQuery;
use App\Cms\Infrastructure\Persistence\PdoDashboardQuery;
use App\Cms\Infrastructure\Persistence\PdoInquiryQuery;
use App\Cms\Infrastructure\Persistence\PdoInquiryRepository;
use App\Cms\Infrastructure\Persistence\LoginAttemptEmailHash;
use App\Cms\Infrastructure\Persistence\PdoLoginAttemptQuery;
use App\Cms\Infrastructure\Persistence\PdoLoginAttemptRepository;
use App\Cms\Infrastructure\Persistence\PdoCmsLoginAttemptResetRepository;
use App\Cms\Infrastructure\Persistence\PdoMediaQuery;
use App\Cms\Infrastructure\Persistence\PdoMediaRepository;
use App\Cms\Infrastructure\Persistence\PdoPageEditQuery;
use App\Cms\Infrastructure\Persistence\PdoPageEditImageQuery;
use App\Cms\Infrastructure\Persistence\PdoPageHistoryQuery;
use App\Cms\Infrastructure\Persistence\PdoPageQuery;
use App\Cms\Infrastructure\Persistence\PdoPageRepository;
use App\Cms\Infrastructure\Persistence\PdoSettingRepository;
use App\Cms\Infrastructure\Persistence\PdoCategoryQuery;
use App\Cms\Infrastructure\Persistence\PdoCategoryRepository;
use App\Cms\Infrastructure\Persistence\PdoTagQuery;
use App\Cms\Infrastructure\Persistence\PdoTagRepository;
use App\Cms\Infrastructure\Persistence\PdoTrashQuery;
use App\Cms\Infrastructure\Persistence\PdoUserRepository;
use App\Cms\Infrastructure\Auth\Authorization\CodeDefinedRolePermissionQuery;
use App\Cms\Infrastructure\Auth\Password\NativePasswordVerifier;
use App\Cms\Infrastructure\Auth\Password\NativePasswordHasher;
use App\Cms\Infrastructure\Logging\JsonFileAuditLogQuery;
use App\Cms\Presentation\Http\Middleware\CsrfSecurityLogMiddleware;
use Pera\Framework\Container\Container;
use Pera\Framework\Security\Csrf;
use Pera\Framework\Security\CsrfMiddleware;
use Pera\Framework\View\ITemplateRenderer;
use App\Shared\Cors\Infrastructure\Http\CorsMiddleware;
use App\Shared\Event\Application\IEventBus;
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
    allowedOrigins: $csv($envString('CMS_CORS_ALLOWED_ORIGINS')),
    allowedMethods: $csv($envString('CMS_CORS_ALLOWED_METHODS', 'GET,POST,OPTIONS')),
    allowedHeaders: $csv($envString('CMS_CORS_ALLOWED_HEADERS', 'Content-Type,X-Requested-With')),
    allowCredentials: filter_var($envString('CMS_CORS_ALLOW_CREDENTIALS', 'false'), FILTER_VALIDATE_BOOL),
    maxAge: (int) $envString('CMS_CORS_MAX_AGE', '600'),
));

$container->singleton(id: CsrfMiddleware::class, factory: function (Container $container) use ($csv, $envString): CsrfSecurityLogMiddleware {
    $csrf = $container->get(id: Csrf::class);
    if (!$csrf instanceof Csrf) {
        throw new \RuntimeException('CSRF service is not registered.');
    }
    $events = $container->get(id: IEventBus::class);
    if (!$events instanceof IEventBus) {
        throw new \RuntimeException('Event bus service is not registered.');
    }

    $appEnv = strtolower($envString('APP_ENV', 'production'));

    return new CsrfSecurityLogMiddleware(
        csrf: $csrf,
        events: $events,
        excludedPaths: $csv($envString('CSRF_EXCLUDED_PATHS')),
        enabled: !in_array($appEnv, ['stg', 'staging', 'dev', 'develop', 'development', 'test'], true),
    );
});

$container->singleton(id: ITemplateRenderer::class, factory: function () use (&$container, $root): ITemplateRenderer {
    $renderer = new LatteTemplateRenderer(
        templateDirectory: $root . '/apps/cms/templates',
    );
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
$container->bind(abstract: Auth\Login\Port\IPasswordVerifier::class, concrete: NativePasswordVerifier::class);
$container->singleton(id: LoginAttemptEmailHash::class, factory: static fn (): LoginAttemptEmailHash => new LoginAttemptEmailHash(
    key: $envString('CMS_LOGIN_ATTEMPT_HASH_KEY', $envString('APP_URL', 'webmedia')),
));
$container->bind(abstract: Auth\Login\Query\ILoginAttemptQuery::class, concrete: PdoLoginAttemptQuery::class);
$container->bind(abstract: ILoginAttemptRepository::class, concrete: PdoLoginAttemptRepository::class);
$container->bind(abstract: ICmsLoginAttemptResetRepository::class, concrete: PdoCmsLoginAttemptResetRepository::class);
$container->bind(abstract: AdminUser\Create\Port\IPasswordHasher::class, concrete: NativePasswordHasher::class);
$container->bind(abstract: AdminUser\Update\Port\IPasswordHasher::class, concrete: NativePasswordHasher::class);
$container->bind(abstract: Auth\AuthorizeRoute\Query\IRolePermissionQuery::class, concrete: CodeDefinedRolePermissionQuery::class);
$container->bind(abstract: Auth\GetCurrentUser\Query\ICurrentCmsUserQuery::class, concrete: PdoCurrentCmsUserQuery::class);
$container->bind(abstract: IArticleRepository::class, concrete: PdoArticleRepository::class);
$container->bind(abstract: IMediaRepository::class, concrete: PdoMediaRepository::class);
$container->bind(abstract: IPageRepository::class, concrete: PdoPageRepository::class);
$container->bind(abstract: ICategoryRepository::class, concrete: PdoCategoryRepository::class);
$container->bind(abstract: ITagRepository::class, concrete: PdoTagRepository::class);
$container->bind(abstract: ISettingRepository::class, concrete: PdoSettingRepository::class);
$container->bind(abstract: Article\List\Query\IArticleQuery::class, concrete: PdoArticleQuery::class);
$container->bind(abstract: Article\Edit\Query\IArticleQuery::class, concrete: PdoArticleEditQuery::class);
$container->bind(abstract: Article\Edit\Query\ICategoryQuery::class, concrete: PdoArticleEditCategoryQuery::class);
$container->bind(abstract: Article\Edit\Query\IImageQuery::class, concrete: PdoArticleEditImageQuery::class);
$container->bind(abstract: Article\Preview\Query\IArticleQuery::class, concrete: PdoArticlePreviewQuery::class);
$container->bind(abstract: ArticleVersion\Preview\Query\IArticleVersionQuery::class, concrete: PdoArticleVersionPreviewQuery::class);
$container->bind(abstract: Article\History\Query\IArticleHistoryQuery::class, concrete: PdoArticleHistoryQuery::class);
$container->bind(abstract: Dashboard\Show\Query\IDashboardQuery::class, concrete: PdoDashboardQuery::class);
$container->bind(abstract: Inquiry\List\Query\IInquiryQuery::class, concrete: PdoInquiryQuery::class);
$container->bind(abstract: Inquiry\Detail\Query\IInquiryQuery::class, concrete: PdoInquiryQuery::class);
$container->bind(abstract: IInquiryRepository::class, concrete: PdoInquiryRepository::class);
$container->set(id: Audit\List\Query\IAuditLogQuery::class, factory: static fn (): JsonFileAuditLogQuery => new JsonFileAuditLogQuery($root . '/storage/logs/audit.log'));
$container->bind(abstract: Media\List\Query\IMediaQuery::class, concrete: PdoMediaQuery::class);
$container->bind(abstract: Page\List\Query\IPageQuery::class, concrete: PdoPageQuery::class);
$container->bind(abstract: Page\Edit\Query\IPageQuery::class, concrete: PdoPageEditQuery::class);
$container->bind(abstract: Page\Edit\Query\IImageQuery::class, concrete: PdoPageEditImageQuery::class);
$container->bind(abstract: Page\History\Query\IPageHistoryQuery::class, concrete: PdoPageHistoryQuery::class);
$container->bind(abstract: Setting\Edit\Query\ISettingQuery::class, concrete: PdoSettingRepository::class);
$container->bind(abstract: Category\List\Query\ICategoryQuery::class, concrete: PdoCategoryQuery::class);
$container->bind(abstract: Tag\List\Query\ITagQuery::class, concrete: PdoTagQuery::class);
$container->bind(abstract: Trash\List\Query\ITrashQuery::class, concrete: PdoTrashQuery::class);
$container->bind(abstract: IAdminUserRepository::class, concrete: PdoAdminUserRepository::class);
$container->bind(abstract: AdminUser\List\Query\IAdminUserQuery::class, concrete: PdoAdminUserRepository::class);
$container->bind(abstract: Role\List\Query\IRolePermissionQuery::class, concrete: CodeDefinedRolePermissionQuery::class);
$container->bind(abstract: Permission\List\Query\IRolePermissionQuery::class, concrete: CodeDefinedRolePermissionQuery::class);

return $container;
