<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Application\Auth\AuthorizeRoute\Value\PermissionCode;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Cms\Presentation\Http\Middleware\AuditMiddleware;
use App\Cms\Presentation\Http\Middleware\RequireCmsPermissionMiddleware;
use App\Cms\Presentation\Http\Middleware\RequireCmsSessionMiddleware;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CmsConfigTest extends TestCase
{
    /** @var list<string> */
    private const AUDIT_REPORTER_EXCLUDED_STATE_CHANGING_PATHS = [
        '/admin/login',
        '/admin/logout',
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

    public function testCmsWriteRoutesDeclareRequiredPermissionMetadata(): void
    {
        $routes = require dirname(__DIR__, 2) . '/apps/cms/config/routes.php';
        self::assertIsArray($routes);

        /** @var array<int, array{0:string,1:string,2:mixed,3?:array<string, mixed>}> $routes */
        self::assertSame(PermissionCode::ARTICLE_CREATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/articles'));
        self::assertSame(PermissionCode::ARTICLE_UPDATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/articles/{id:\d+}'));
        self::assertSame(PermissionCode::ARTICLE_DELETE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/articles/{id:\d+}/delete'));
        self::assertSame(PermissionCode::SETTING_UPDATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/settings'));
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

    public function testCmsStateChangingRoutesDeclareRequiredPermissionMetadataExceptAuthSecurityRoutes(): void
    {
        $routes = require dirname(__DIR__, 2) . '/apps/cms/config/routes.php';
        self::assertIsArray($routes);

        /** @var array<int, array{0:string,1:string,2:mixed,3?:array<string, mixed>}> $routes */
        foreach ($routes as $route) {
            [$method, $path] = $route;
            if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                continue;
            }

            if (in_array($path, ['/admin/login', '/admin/logout'], true)) {
                continue;
            }

            $metadata = $route[3] ?? [];
            self::assertArrayHasKey(
                RequireCmsPermissionMiddleware::class,
                $metadata,
                "{$method} {$path} must declare middleware permission metadata.",
            );
            self::assertIsString($metadata[RequireCmsPermissionMiddleware::class]);
            self::assertNotSame('', $metadata[RequireCmsPermissionMiddleware::class]);
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
