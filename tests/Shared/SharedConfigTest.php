<?php

declare(strict_types=1);

namespace Tests\Shared;

use App\Framework\Container\Container;
use App\Shared\Audit\Application\AuditLoggerInterface;
use App\Shared\Audit\Infrastructure\PlaceholderAuditLogger;
use App\Shared\Logging\Infrastructure\JsonFileLogger;
use App\Shared\Markdown\Infrastructure\Markdown;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
        self::assertInstanceOf(JsonFileLogger::class, $container->get(id: LoggerInterface::class));
        self::assertInstanceOf(PlaceholderAuditLogger::class, $container->get(id: AuditLoggerInterface::class));
    }

    public function testSharedMiddlewareConfigIsArray(): void
    {
        $middleware = require dirname(__DIR__, 2) . '/apps/shared/config/middleware.php';

        self::assertIsArray($middleware);
    }
}
