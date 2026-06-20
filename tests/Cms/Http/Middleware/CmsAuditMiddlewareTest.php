<?php

declare(strict_types=1);

namespace Tests\Cms\Http\Middleware;

use App\Cms\Infrastructure\Http\Middleware\CmsAuditMiddleware;
use App\Framework\Http\Middleware\CallableRequestHandler;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use App\Shared\Audit\Application\AuditLoggerInterface;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Audit\Infrastructure\PlaceholderAuditLogger;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CmsAuditMiddlewareTest extends TestCase
{
    public function testGetAdminRequestIsNotRecorded(): void
    {
        $logger = new RecordingAuditLogger();
        $middleware = new CmsAuditMiddleware(auditLogger: $logger, session: $this->session());

        $response = $middleware->process(
            request: $this->request(method: 'GET', path: '/admin/articles'),
            handler: $this->handler(response: new Response(body: 'ok')),
        );

        self::assertSame(200, $response->status);
        self::assertSame([], $logger->events);
    }

    public function testNonAdminPrefixIsNotRecorded(): void
    {
        $logger = new RecordingAuditLogger();
        $middleware = new CmsAuditMiddleware(auditLogger: $logger, session: $this->session());

        $middleware->process(
            request: $this->request(method: 'POST', path: '/administer/articles'),
            handler: $this->handler(response: new Response(body: 'ok')),
        );

        self::assertSame([], $logger->events);
    }

    public function testPostAdminRequestIsRecorded(): void
    {
        $logger = new RecordingAuditLogger();
        $session = $this->session();
        $session->set(key: 'cms.user_id', value: 42);
        $middleware = new CmsAuditMiddleware(auditLogger: $logger, session: $session);

        $response = $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/articles/10'),
            handler: $this->handler(response: Response::redirect(location: '/admin/articles/10')),
        );

        self::assertSame(302, $response->status);
        self::assertCount(1, $logger->events);

        $event = $logger->events[0];
        self::assertSame('cms.http_state_changed', $event->action);
        self::assertSame('cms_request', $event->resourceType);
        self::assertSame('success', $event->result);
        self::assertSame('42', $event->actorUserId);
        self::assertNull($event->actorRole);
        self::assertNull($event->resourceId);
        self::assertSame('127.0.0.1', $event->ipAddress);
        self::assertSame('PHPUnit Browser', $event->userAgent);
        self::assertSame('request-123', $event->requestId);
        self::assertSame([], $event->before);
        self::assertSame([], $event->after);
        self::assertSame([
            'method' => 'POST',
            'path' => '/admin/articles/10',
            'status_code' => 302,
        ], $event->metadata);
    }

    public function testUnknownAdminStateChangingRequestIsRecorded(): void
    {
        $logger = new RecordingAuditLogger();
        $middleware = new CmsAuditMiddleware(auditLogger: $logger, session: $this->session());

        $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/unknown-operation'),
            handler: $this->handler(response: new Response(body: 'ok')),
        );

        self::assertCount(1, $logger->events);
        self::assertSame('/admin/unknown-operation', $logger->events[0]->metadata['path'] ?? null);
    }

    public function testLoginAndLogoutAreNotRecorded(): void
    {
        $logger = new RecordingAuditLogger();
        $middleware = new CmsAuditMiddleware(auditLogger: $logger, session: $this->session());

        $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/login'),
            handler: $this->handler(response: new Response(body: 'login')),
        );
        $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/logout'),
            handler: $this->handler(response: Response::redirect(location: '/admin/login')),
        );

        self::assertSame([], $logger->events);
    }

    public function testResponseWithClientOrServerErrorIsRecordedAsFailure(): void
    {
        $logger = new RecordingAuditLogger();
        $middleware = new CmsAuditMiddleware(auditLogger: $logger, session: $this->session());

        $middleware->process(
            request: $this->request(method: 'PATCH', path: '/admin/articles/10'),
            handler: $this->handler(response: new Response(body: 'invalid', status: 422)),
        );

        self::assertCount(1, $logger->events);
        self::assertSame('failure', $logger->events[0]->result);
        self::assertSame(422, $logger->events[0]->metadata['status_code'] ?? null);
    }

    public function testHandlerExceptionIsRecordedAsFailureAndOriginalExceptionIsRethrown(): void
    {
        $logger = new RecordingAuditLogger();
        $middleware = new CmsAuditMiddleware(auditLogger: $logger, session: $this->session());
        $exception = new RuntimeException('handler failed');

        try {
            $middleware->process(
                request: $this->request(method: 'DELETE', path: '/admin/articles/10'),
                handler: new CallableRequestHandler(handler: static function () use ($exception): Response {
                    throw $exception;
                }),
            );
            self::fail('Expected handler exception.');
        } catch (RuntimeException $caught) {
            self::assertSame($exception, $caught);
        }

        self::assertCount(1, $logger->events);
        self::assertSame('failure', $logger->events[0]->result);
        self::assertSame(500, $logger->events[0]->metadata['status_code'] ?? null);
    }

    public function testAuditLoggerExceptionIsVisibleWhenRecordingHandlerFailure(): void
    {
        $middleware = new CmsAuditMiddleware(auditLogger: new ThrowingAuditLogger(), session: $this->session());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('audit failed');

        $middleware->process(
            request: $this->request(method: 'DELETE', path: '/admin/articles/10'),
            handler: new CallableRequestHandler(handler: static function (): Response {
                throw new RuntimeException('handler failed');
            }),
        );
    }

    public function testPlaceholderAuditLoggerThrowsForAuditedRequest(): void
    {
        $middleware = new CmsAuditMiddleware(auditLogger: new PlaceholderAuditLogger(), session: $this->session());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logger is not configured.');

        $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/articles/10'),
            handler: $this->handler(response: new Response(body: 'ok')),
        );
    }

    public function testRequestBodySecretsAreNotStoredInMetadata(): void
    {
        $logger = new RecordingAuditLogger();
        $middleware = new CmsAuditMiddleware(auditLogger: $logger, session: $this->session());

        $middleware->process(
            request: $this->request(
                method: 'POST',
                path: '/admin/articles/10',
                body: [
                    '_csrf' => 'csrf-token',
                    'password' => 'secret-password',
                    'title' => 'Title',
                ],
            ),
            handler: $this->handler(response: new Response(body: 'ok')),
        );

        self::assertCount(1, $logger->events);
        self::assertArrayNotHasKey('_csrf', $logger->events[0]->metadata);
        self::assertArrayNotHasKey('password', $logger->events[0]->metadata);
        self::assertArrayNotHasKey('title', $logger->events[0]->metadata);
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
                'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
            ],
        );
    }

    private function handler(Response $response): CallableRequestHandler
    {
        return new CallableRequestHandler(handler: static fn (): Response => $response);
    }

    private function session(): Session
    {
        $data = [];

        return new Session(state: new SessionState(data: $data), runtime: new NativeSessionRuntime());
    }
}

final class RecordingAuditLogger implements AuditLoggerInterface
{
    /** @var array<int, AuditEvent> */
    public array $events = [];

    public function record(AuditEvent $event): void
    {
        $this->events[] = $event;
    }
}

final readonly class ThrowingAuditLogger implements AuditLoggerInterface
{
    public function record(AuditEvent $event): void
    {
        throw new LogicException('audit failed');
    }
}
