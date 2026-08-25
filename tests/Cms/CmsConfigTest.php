<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Application\Auth\AuthorizeRoute\Value\PermissionCode;
use App\Cms\Infrastructure\Logging\NonBlockingAuditLogger;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Cms\Presentation\Http\Middleware\AuditMiddleware;
use App\Cms\Presentation\Http\Middleware\RequireCmsPermissionMiddleware;
use App\Cms\Presentation\Http\Middleware\RequireCmsSessionMiddleware;
use App\Shared\Audit\Application\IAuditLogger;
use Pera\Framework\Container\Container;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CmsConfigTest extends TestCase
{
    /** @var list<string> */
    private const AUDIT_REPORTER_EXCLUDED_STATE_CHANGING_PATHS = [
        '/admin/login',
        '/admin/logout',
    ];

    /** @var list<string> */
    private const AUTHENTICATION_ROUTES = [
        'GET /admin/login',
        'POST /admin/login',
        'POST /admin/logout',
    ];

    public function testCmsMiddlewareConfigIncludesAuditAfterSessionRequirement(): void
    {
        $middleware = require dirname(__DIR__, 2) . '/apps/cms/config/middleware.php';

        self::assertIsArray($middleware);

        $sessionIndex = array_search(RequireCmsSessionMiddleware::class, $middleware, true);
        $permissionIndex = array_search(RequireCmsPermissionMiddleware::class, $middleware, true);
        $auditIndex = array_search(AuditMiddleware::class, $middleware, true);

        self::assertIsInt($sessionIndex);
        self::assertIsInt($permissionIndex);
        self::assertIsInt($auditIndex);
        self::assertGreaterThan($sessionIndex, $permissionIndex);
        self::assertGreaterThan($permissionIndex, $auditIndex);
    }

    public function testCmsChoosesNonBlockingAuditPersistencePolicy(): void
    {
        $container = require dirname(__DIR__, 2) . '/apps/cms/config/container.php';
        self::assertInstanceOf(Container::class, $container);

        self::assertInstanceOf(
            NonBlockingAuditLogger::class,
            $container->get(id: IAuditLogger::class),
        );
    }

    public function testCmsRoutesUseExpectedPermissionMetadata(): void
    {
        $routes = require dirname(__DIR__, 2) . '/apps/cms/config/routes.php';
        self::assertIsArray($routes);

        /** @var array<int, array{0:string,1:string,2:mixed,3?:array<string, mixed>}> $routes */
        self::assertSame(PermissionCode::DASHBOARD_VIEW, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin'));
        self::assertSame(PermissionCode::ARTICLE_LIST, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/articles'));
        self::assertSame(PermissionCode::ARTICLE_CREATE, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/articles/create'));
        self::assertSame(PermissionCode::ARTICLE_UPDATE, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/articles/{id:\d+}'));
        self::assertSame(PermissionCode::ARTICLE_PREVIEW, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/articles/{id:\d+}/preview'));
        self::assertSame(PermissionCode::ARTICLE_HISTORY, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/articles/{id:\d+}/history'));
        self::assertSame(PermissionCode::ARTICLE_HISTORY, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/articles/{id:\d+}/history/{version:\d+}/preview'));
        self::assertSame(PermissionCode::ARTICLE_CREATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/articles'));
        self::assertSame(PermissionCode::ARTICLE_UPDATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/articles/{id:\d+}'));
        self::assertSame(PermissionCode::ARTICLE_DELETE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/articles/{id:\d+}/delete'));
        self::assertSame(PermissionCode::PAGE_LIST, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/pages'));
        self::assertSame(PermissionCode::PAGE_CREATE, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/pages/create'));
        self::assertSame(PermissionCode::PAGE_UPDATE, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/pages/{id:\d+}'));
        self::assertSame(PermissionCode::TAXONOMY_MANAGE, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/categories'));
        self::assertSame(PermissionCode::TAXONOMY_MANAGE, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/tags'));
        self::assertSame(PermissionCode::MEDIA_LIST, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/media'));
        self::assertSame(PermissionCode::INQUIRY_READ, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/inquiries'));
        self::assertSame(PermissionCode::SETTING_UPDATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/settings'));
        self::assertSame(PermissionCode::SETTING_UPDATE, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/settings'));
        self::assertSame(PermissionCode::TRASH_VIEW, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/trash'));
        self::assertSame(PermissionCode::AUDIT_VIEW, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/audit-logs'));
        self::assertSame(PermissionCode::INQUIRY_READ, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/inquiries/{id:\d+}'));
        self::assertSame(PermissionCode::INQUIRY_UPDATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/inquiries/{id:\d+}'));
        self::assertSame(PermissionCode::PAGE_UPDATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/pages/{id:\d+}/restore/{version:\d+}'));
        self::assertSame(PermissionCode::ADMIN_USER_MANAGE, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/users'));
        self::assertSame(PermissionCode::ADMIN_USER_MANAGE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/users'));
        self::assertSame(PermissionCode::ADMIN_USER_MANAGE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/users/{id:\d+}'));
        self::assertSame(PermissionCode::ADMIN_USER_MANAGE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/users/{id:\d+}/delete'));
        self::assertSame(PermissionCode::ROLE_VIEW, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/roles'));
        self::assertSame(PermissionCode::PERMISSION_VIEW, $this->permissionFor(routes: $routes, method: 'GET', path: '/admin/permissions'));
    }

    public function testCmsRoutesDeclareValidPermissionMetadataExceptAuthenticationRoutes(): void
    {
        $routes = require dirname(__DIR__, 2) . '/apps/cms/config/routes.php';
        self::assertIsArray($routes);

        /** @var array<int, array{0:string,1:string,2:mixed,3?:array<string, mixed>}> $routes */
        foreach ($routes as $route) {
            [$method, $path] = $route;
            $metadata = $route[3] ?? [];
            if (in_array("{$method} {$path}", self::AUTHENTICATION_ROUTES, true)) {
                self::assertArrayNotHasKey(RequireCmsPermissionMiddleware::class, $metadata);
                continue;
            }

            self::assertArrayHasKey(
                RequireCmsPermissionMiddleware::class,
                $metadata,
                "{$method} {$path} must declare middleware permission metadata.",
            );
            self::assertIsString($metadata[RequireCmsPermissionMiddleware::class]);
            self::assertNotSame('', $metadata[RequireCmsPermissionMiddleware::class]);
            self::assertContains($metadata[RequireCmsPermissionMiddleware::class], PermissionCode::all());
        }
    }

    public function testCmsStateChangingRoutesUseAuditReporterExceptExplicitSecurityLogRoutes(): void
    {
        $routes = require dirname(__DIR__, 2) . '/apps/cms/config/routes.php';
        self::assertIsArray($routes);

        $violations = [];
        /** @var array<int, array{0:string,1:string,2:mixed,3?:array<string, mixed>}> $routes */
        foreach ($routes as $route) {
            [$method, $path, $controller] = $route;
            if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                continue;
            }

            if (in_array($path, self::AUDIT_REPORTER_EXCLUDED_STATE_CHANGING_PATHS, true)) {
                continue;
            }

            if (!is_string($controller) || !class_exists($controller)) {
                $violations[] = "{$method} {$path} has an invalid controller.";
                continue;
            }

            if (!$this->controllerInjects(controller: $controller, dependency: AuditReporter::class)) {
                $violations[] = "{$method} {$path} {$controller} must inject AuditReporter or be explicitly excluded.";
            }
            if (!$this->controllerCallsAuditReporter(controller: $controller)) {
                $violations[] = "{$method} {$path} {$controller} must call AuditReporter on successful state change.";
            }
        }

        self::assertSame([], $violations);
    }

    /**
     * @param array<int, array{0:string,1:string,2:mixed,3?:array<string, mixed>}> $routes
     */
    private function permissionFor(array $routes, string $method, string $path): ?string
    {
        foreach ($routes as $route) {
            if ($route[0] === $method && $route[1] === $path) {
                $metadata = $route[3] ?? [];
                $permission = $metadata[RequireCmsPermissionMiddleware::class] ?? null;

                return is_string($permission) ? $permission : null;
            }
        }

        return null;
    }

    /**
     * @param class-string $controller
     * @param class-string $dependency
     */
    private function controllerInjects(string $controller, string $dependency): bool
    {
        $constructor = (new ReflectionClass($controller))->getConstructor();
        if ($constructor === null) {
            return false;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && $type->getName() === $dependency) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string $controller
     */
    private function controllerCallsAuditReporter(string $controller): bool
    {
        $fileName = (new ReflectionClass($controller))->getFileName();
        if ($fileName === false) {
            return false;
        }

        $contents = file_get_contents($fileName);
        self::assertIsString($contents);

        return str_contains($contents, '->audit->');
    }
}
