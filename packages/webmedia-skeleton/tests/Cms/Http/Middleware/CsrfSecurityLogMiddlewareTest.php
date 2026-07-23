<?php

declare(strict_types=1);

namespace Tests\Cms\Http\Middleware;

use App\Cms\Presentation\Http\Middleware\CsrfSecurityLogMiddleware;
use Pera\Framework\Http\Middleware\CallableRequestHandler;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use Pera\Framework\Security\SessionState;
use App\Shared\Event\Application\IEventBus;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;
use PHPUnit\Framework\TestCase;

final class CsrfSecurityLogMiddlewareTest extends TestCase
{
    public function testInvalidCsrfTokenRecordsSecurityLog(): void
    {
        $sessionData = ['_csrf' => 'valid-token'];
        $session = new Session(state: new SessionState(data: $sessionData), runtime: new NativeSessionRuntime());
        $events = new RecordingCsrfEventBus();
        $middleware = new CsrfSecurityLogMiddleware(
            csrf: new Csrf(session: $session),
            events: $events,
        );

        $response = $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/articles', body: ['_csrf' => 'invalid-token']),
            handler: $this->handler(),
        );

        self::assertSame(422, $response->status);
        self::assertSame('Invalid CSRF token', $response->body);
        self::assertCount(1, $events->events);

        $event = $events->events[0];
        self::assertInstanceOf(SecurityLogEvent::class, $event);
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
        $events = new RecordingCsrfEventBus();
        $middleware = new CsrfSecurityLogMiddleware(
            csrf: new Csrf(session: $session),
            events: $events,
        );

        $response = $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/articles', body: ['_csrf' => 'valid-token']),
            handler: $this->handler(),
        );

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body);
        self::assertSame([], $events->events);
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

final class RecordingCsrfEventBus implements IEventBus
{
    /** @var list<object> */
    public array $events = [];

    public function dispatch(object $event): void
    {
        $this->events[] = $event;
    }
}
