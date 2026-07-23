<?php

declare(strict_types=1);

namespace Tests;

use App\Kernel\Application;
use Pera\Framework\Bootstrap\ApplicationBootstrapConfig;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\CsrfMiddleware;
use Pera\Framework\Security\Session;
use PHPUnit\Framework\TestCase;

final class InitialApplicationTest extends TestCase
{
    public function testInitialPageReturnsHtmlWithoutStartingSession(): void
    {
        self::assertSame(PHP_SESSION_NONE, session_status());

        $root = dirname(__DIR__);
        $middleware = require $root . '/apps/shared/config/middleware.php';
        self::assertIsArray($middleware);
        self::assertContains(CsrfMiddleware::class, $middleware);

        $applicationFactory = require $root . '/apps/kernel/config/application.php';
        self::assertInstanceOf(\Closure::class, $applicationFactory);

        $application = $applicationFactory(new ApplicationBootstrapConfig(root: $root));
        self::assertInstanceOf(Application::class, $application);

        $response = $application->handle(
            request: new Request(method: 'GET', path: '/', query: [], body: [], server: []),
            session: Session::fromNativeGlobals(runtime: new NativeSessionRuntime(), secure: false),
        );

        self::assertSame(Response::HTTP_OK, $response->status);
        self::assertSame('text/html; charset=utf-8', $response->headers['Content-Type'] ?? null);
        self::assertSame('nosniff', $response->headers['X-Content-Type-Options'] ?? null);
        self::assertStringContainsString('minimal is running.', $response->body);
        self::assertSame(PHP_SESSION_NONE, session_status());
    }
}
