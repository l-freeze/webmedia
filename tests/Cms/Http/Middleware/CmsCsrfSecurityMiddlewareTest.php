<?php

declare(strict_types=1);

namespace Tests\Cms\Http\Middleware;

use App\Cms\Infrastructure\Http\Middleware\CmsCsrfSecurityMiddleware;
use App\Framework\Http\Middleware\CallableRequestHandler;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use App\Shared\SecurityLog\Application\SecurityLoggerInterface;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;
use PHPUnit\Framework\TestCase;

final class CmsCsrfSecurityMiddlewareTest extends TestCase
{
    public function testInvalidCsrfTokenRecordsSecurityLog(): void
    {
        $sessionData = ['_csrf' => 'valid-token'];
        $session = new Session(state: new SessionState(data: $sessionData), runtime: new NativeSessionRuntime());
        $securityLogger = new RecordingCsrfSecurityLogger();
        $middleware = new CmsCsrfSecurityMiddleware(
            csrf: new Csrf(session: $session),
            securityLogger: $securityLogger,
        );

        $response = $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/articles', body: ['_csrf' => 'invalid-token']),
            handler: $this->handler(),
        );

        self::assertSame(422, $response->status);
        self::assertSame('Invalid CSRF token', $response->body);
        self::assertCount(1, $securityLogger->events);

        $event = $securityLogger->events[0];
        self::assertSame('cms.csrf.denied', $event->eventType);
        self::assertSame('failure', $event->result);
        self::assertSame('127.0.0.1', $event->ipAddress);
        self::assertSame('PHPUnit Browser', $event->userAgent);
        self::assertSame('request-123', $event->requestId);
        self::assertSame('POST', $event->metadata['method'] ?? null);
        self::assertSame('/admin/articles', $event->metadata['path'] ?? null);
        self::assertArrayNotHasKey('_csrf', $event->metadata);
    }

    public function testValidCsrfTokenAllowsRequestWithoutSecurityLog(): void
    {
        $sessionData = ['_csrf' => 'valid-token'];
        $session = new Session(state: new SessionState(data: $sessionData), runtime: new NativeSessionRuntime());
        $securityLogger = new RecordingCsrfSecurityLogger();
        $middleware = new CmsCsrfSecurityMiddleware(
            csrf: new Csrf(session: $session),
            securityLogger: $securityLogger,
        );

        $response = $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/articles', body: ['_csrf' => 'valid-token']),
            handler: $this->handler(),
        );

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body);
        self::assertSame([], $securityLogger->events);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $method, string $path, array $body = []): Request
    {
        return new Request(
            method: $method,
            path: $path,
            query: [],
            body: $body,
            server: [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit Browser',
                'HTTP_X_REQUEST_ID' => 'request-123',
            ],
        );
    }

    private function handler(): CallableRequestHandler
    {
        return new CallableRequestHandler(handler: static fn (): Response => new Response(body: 'ok'));
    }
}

final class RecordingCsrfSecurityLogger implements SecurityLoggerInterface
{
    /** @var list<SecurityLogEvent> */
    public array $events = [];

    public function record(SecurityLogEvent $event): void
    {
        $this->events[] = $event;
    }
}
