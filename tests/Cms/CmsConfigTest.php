<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Application\Auth\Authorization\Permission;
use App\Cms\Infrastructure\Http\Middleware\CmsAuditMiddleware;
use App\Cms\Infrastructure\Http\Middleware\RequireCmsPermissionMiddleware;
use App\Cms\Infrastructure\Http\Middleware\RequireCmsSessionMiddleware;
use PHPUnit\Framework\TestCase;

final class CmsConfigTest extends TestCase
{
    public function testCmsMiddlewareConfigIncludesAuditAfterSessionRequirement(): void
    {
        $middleware = require dirname(__DIR__, 2) . '/apps/cms/config/middleware.php';

        self::assertIsArray($middleware);

        $sessionIndex = array_search(RequireCmsSessionMiddleware::class, $middleware, true);
        $permissionIndex = array_search(RequireCmsPermissionMiddleware::class, $middleware, true);
        $auditIndex = array_search(CmsAuditMiddleware::class, $middleware, true);

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
        self::assertSame(Permission::ARTICLE_CREATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/articles'));
        self::assertSame(Permission::ARTICLE_UPDATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/articles/{id:\d+}'));
        self::assertSame(Permission::ARTICLE_DELETE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/articles/{id:\d+}/delete'));
        self::assertSame(Permission::SETTING_UPDATE, $this->permissionFor(routes: $routes, method: 'POST', path: '/admin/settings'));
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
            self::assertArrayHasKey('requires_permission', $metadata, "{$method} {$path} must declare requires_permission.");
            self::assertIsString($metadata['requires_permission']);
            self::assertNotSame('', $metadata['requires_permission']);
        }
    }

    /**
     * @param array<int, array{0:string,1:string,2:mixed,3?:array<string, mixed>}> $routes
     */
    private function permissionFor(array $routes, string $method, string $path): ?string
    {
        foreach ($routes as $route) {
            if ($route[0] === $method && $route[1] === $path) {
                $metadata = $route[3] ?? [];
                $permission = $metadata['requires_permission'] ?? null;

                return is_string($permission) ? $permission : null;
            }
        }

        return null;
    }
}
