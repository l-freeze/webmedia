<?php

declare(strict_types=1);

namespace Tests\Cms\Http\Middleware;

use App\Cms\Application\Auth\CurrentUser\Query\ICurrentCmsUserQuery;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Cms\Presentation\Audit\CmsAuditSummary;
use App\Cms\Presentation\Audit\CmsAuditTrail;
use App\Cms\Presentation\Http\Middleware\CmsAuditMiddleware;
use App\Framework\Http\Middleware\CallableRequestHandler;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use App\Shared\Audit\Application\RecordAuditEventHandler;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Audit\Infrastructure\PlaceholderAuditLogger;
use App\Shared\Event\Application\EventBusInterface;
use App\Shared\Event\Infrastructure\SynchronousEventBus;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CmsAuditMiddlewareTest extends TestCase
{
    public function testGetAdminRequestIsNotRecorded(): void
    {
        $events = new RecordingEventBus();
        $middleware = $this->middleware(events: $events);

        $response = $middleware->process(
            request: $this->request(method: 'GET', path: '/admin/articles'),
            handler: $this->handler(response: new Response(body: 'ok')),
        );

        self::assertSame(200, $response->status);
        self::assertSame([], $events->events);
    }

    public function testNonAdminPrefixIsNotRecorded(): void
    {
        $events = new RecordingEventBus();
        $middleware = $this->middleware(events: $events);

        $middleware->process(
            request: $this->request(method: 'POST', path: '/administer/articles'),
            handler: $this->handler(response: new Response(body: 'ok')),
        );

        self::assertSame([], $events->events);
    }

    public function testPostAdminRequestIsRecorded(): void
    {
        $events = new RecordingEventBus();
        $session = $this->session();
        $session->set(key: 'cms.user_id', value: 42);
        $middleware = $this->middleware(
            events: $events,
            guard: $this->guard(session: $session, user: new CurrentCmsUser(
                id: 42,
                email: 'editor@example.test',
                name: 'Editor',
                role: 'editor',
            )),
        );

        $response = $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/articles/10'),
            handler: $this->handler(response: Response::redirect(location: '/admin/articles/10')),
        );

        self::assertSame(302, $response->status);
        self::assertCount(1, $events->events);

        $event = $events->events[0];
        self::assertInstanceOf(AuditEvent::class, $event);
        self::assertSame('cms.http_state_changed', $event->action);
        self::assertSame('cms_request', $event->resourceType);
        self::assertSame('success', $event->result);
        self::assertSame('42', $event->actorUserId);
        self::assertSame('editor', $event->actorRole);
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
        $events = new RecordingEventBus();
        $middleware = $this->middleware(events: $events);

        $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/unknown-operation'),
            handler: $this->handler(response: new Response(body: 'ok')),
        );

        self::assertCount(1, $events->events);
        self::assertInstanceOf(AuditEvent::class, $events->events[0]);
        self::assertSame('/admin/unknown-operation', $events->events[0]->metadata['path'] ?? null);
    }

    public function testLoginAndLogoutAreNotRecorded(): void
    {
        $events = new RecordingEventBus();
        $middleware = $this->middleware(events: $events);

        $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/login'),
            handler: $this->handler(response: new Response(body: 'login')),
        );
        $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/logout'),
            handler: $this->handler(response: Response::redirect(location: '/admin/login')),
        );

        self::assertSame([], $events->events);
    }

    public function testResponseWithClientOrServerErrorIsRecordedAsFailure(): void
    {
        $events = new RecordingEventBus();
        $middleware = $this->middleware(events: $events);

        $middleware->process(
            request: $this->request(method: 'PATCH', path: '/admin/articles/10'),
            handler: $this->handler(response: new Response(body: 'invalid', status: 422)),
        );

        self::assertCount(1, $events->events);
        self::assertInstanceOf(AuditEvent::class, $events->events[0]);
        self::assertSame('failure', $events->events[0]->result);
        self::assertSame(422, $events->events[0]->metadata['status_code'] ?? null);
    }

    public function testHandlerExceptionIsRecordedAsFailureAndOriginalExceptionIsRethrown(): void
    {
        $events = new RecordingEventBus();
        $middleware = $this->middleware(events: $events);
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

        self::assertCount(1, $events->events);
        self::assertInstanceOf(AuditEvent::class, $events->events[0]);
        self::assertSame('failure', $events->events[0]->result);
        self::assertSame(500, $events->events[0]->metadata['status_code'] ?? null);
    }

    public function testAuditLoggerExceptionIsVisibleWhenRecordingHandlerFailure(): void
    {
        $middleware = $this->middleware(events: new ThrowingEventBus());

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
        $middleware = $this->middleware(
            events: new SynchronousEventBus(handlers: [
                new RecordAuditEventHandler(logger: new PlaceholderAuditLogger()),
            ]),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logger is not configured.');

        $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/articles/10'),
            handler: $this->handler(response: new Response(body: 'ok')),
        );
    }

    public function testRequestBodySecretsAreNotStoredInMetadata(): void
    {
        $events = new RecordingEventBus();
        $middleware = $this->middleware(events: $events);

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

        self::assertCount(1, $events->events);
        self::assertInstanceOf(AuditEvent::class, $events->events[0]);
        self::assertArrayNotHasKey('_csrf', $events->events[0]->metadata);
        self::assertArrayNotHasKey('password', $events->events[0]->metadata);
        self::assertArrayNotHasKey('title', $events->events[0]->metadata);
    }

    public function testInjectedBusinessSummaryIsRecordedOnSuccessfulRequest(): void
    {
        $events = new RecordingEventBus();
        $middleware = $this->middleware(events: $events);

        $middleware->process(
            request: $this->request(method: 'POST', path: '/admin/articles/10'),
            handler: new CallableRequestHandler(handler: static function (Request $request): Response {
                $trail = $request->attribute(key: CmsAuditTrail::ATTRIBUTE);
                self::assertInstanceOf(CmsAuditTrail::class, $trail);
                $trail->describe(summary: new CmsAuditSummary(
                    action: 'article.updated',
                    resourceType: 'article',
                    resourceId: '10',
                    metadata: [
                        'title' => 'New title',
                        'slug' => 'new-slug',
                        'status' => 'published',
                    ],
                ));

                return Response::redirect(location: '/admin/articles/10');
            }),
        );

        self::assertCount(1, $events->events);
        $event = $events->events[0];
        self::assertInstanceOf(AuditEvent::class, $event);
        self::assertSame('article.updated', $event->action);
        self::assertSame('article', $event->resourceType);
        self::assertSame('10', $event->resourceId);
        self::assertSame('POST', $event->metadata['method']);
        self::assertSame('/admin/articles/10', $event->metadata['path']);
        self::assertSame(302, $event->metadata['status_code']);
        self::assertSame('New title', $event->metadata['title']);
        self::assertSame('new-slug', $event->metadata['slug']);
        self::assertSame('published', $event->metadata['status']);
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

    private function middleware(EventBusInterface $events, ?CmsGuard $guard = null): CmsAuditMiddleware
    {
        return new CmsAuditMiddleware(
            events: $events,
            guard: $guard ?? $this->guard(),
        );
    }

    private function session(): Session
    {
        $data = [];

        return new Session(state: new SessionState(data: $data), runtime: new NativeSessionRuntime());
    }

    private function guard(?Session $session = null, ?CurrentCmsUser $user = null): CmsGuard
    {
        return new CmsGuard(
            session: $session ?? $this->session(),
            users: new FixedAuditCmsUserQuery(user: $user),
        );
    }
}

final readonly class FixedAuditCmsUserQuery implements ICurrentCmsUserQuery
{
    public function __construct(private ?CurrentCmsUser $user)
    {
    }

    public function findById(int $id): ?CurrentCmsUser
    {
        if ($this->user === null || $this->user->id !== $id) {
            return null;
        }

        return $this->user;
    }
}

final class RecordingEventBus implements EventBusInterface
{
    /** @var list<object> */
    public array $events = [];

    public function dispatch(object $event): void
    {
        $this->events[] = $event;
    }
}

final readonly class ThrowingEventBus implements EventBusInterface
{
    public function dispatch(object $event): void
    {
        throw new LogicException('audit failed');
    }
}
