<?php

declare(strict_types=1);

namespace Tests\Shared;

use Pera\Framework\Container\Container;
use Pera\Framework\Security\CsrfMiddleware;
use App\Shared\Audit\Application\IAuditLogger;
use App\Shared\ApplicationLog\Infrastructure\JsonFileApplicationLogger;
use App\Shared\Audit\Infrastructure\JsonFileAuditLogger;
use App\Shared\Cache\Infrastructure\FileSimpleCache;
use App\Shared\Event\Application\IEventBus;
use App\Shared\Event\Infrastructure\SynchronousEventBus;
use App\Shared\Markdown\Infrastructure\Markdown;
use App\Shared\Security\Infrastructure\Http\SecurityHeadersMiddleware;
use App\Shared\SecurityLog\Application\ISecurityLogger;
use App\Shared\SecurityLog\Infrastructure\JsonFileSecurityLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

final class SharedConfigTest extends TestCase
{
    public function testSharedContainerRegistersCommonServices(): void
    {
        $root = dirname(__DIR__, 2);
        $container = new Container();
        $sharedContainer = require $root . '/apps/shared/config/container.php';
        self::assertIsCallable($sharedContainer);

        $sharedContainer($container, $root);

        self::assertInstanceOf(Markdown::class, $container->get(id: Markdown::class));
        self::assertInstanceOf(JsonFileApplicationLogger::class, $container->get(id: LoggerInterface::class));
        self::assertInstanceOf(JsonFileAuditLogger::class, $container->get(id: IAuditLogger::class));
        self::assertInstanceOf(JsonFileSecurityLogger::class, $container->get(id: ISecurityLogger::class));
        self::assertInstanceOf(SynchronousEventBus::class, $container->get(id: IEventBus::class));
        self::assertInstanceOf(FileSimpleCache::class, $container->get(id: CacheInterface::class));
    }

    public function testSharedMiddlewareConfigIsArray(): void
    {
        $middleware = require dirname(__DIR__, 2) . '/apps/shared/config/middleware.php';

        self::assertIsArray($middleware);
        self::assertContains(SecurityHeadersMiddleware::class, $middleware);
        self::assertContains(CsrfMiddleware::class, $middleware);
    }
}
