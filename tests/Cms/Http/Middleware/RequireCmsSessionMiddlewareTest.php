<?php

declare(strict_types=1);

namespace Tests\Cms\Http\Middleware;

use App\Cms\Application\Auth\CurrentUser\Query\ICurrentCmsUserQuery;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;
use App\Cms\Infrastructure\Http\Middleware\RequireCmsSessionMiddleware;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Middleware\CallableRequestHandler;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use PHPUnit\Framework\TestCase;

final class RequireCmsSessionMiddlewareTest extends TestCase
{
    public function testAllowsPublicLoginPathWithoutSession(): void
    {
        $middleware = new RequireCmsSessionMiddleware(guard: $this->guard(session: $this->session()));

        $response = $middleware->process(
            request: new Request(method: 'GET', path: '/admin/login', query: [], body: [], server: []),
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'login'),
            ),
        );

        self::assertSame(200, $response->status);
        self::assertSame('login', $response->body);
    }

    public function testRedirectsAdminRequestWithoutSession(): void
    {
        $middleware = new RequireCmsSessionMiddleware(guard: $this->guard(session: $this->session()));

        $response = $middleware->process(
            request: new Request(method: 'GET', path: '/admin', query: [], body: [], server: []),
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'admin'),
            ),
        );

        self::assertSame(302, $response->status);
        self::assertSame('/admin/login', $response->headers['Location'] ?? null);
    }

    public function testAllowsAdminRequestWithSession(): void
    {
        $session = $this->session();
        $session->set(key: 'cms.user_id', value: 1);
        $middleware = new RequireCmsSessionMiddleware(guard: $this->guard(session: $session));

        $response = $middleware->process(
            request: new Request(method: 'GET', path: '/admin', query: [], body: [], server: []),
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'admin'),
            ),
        );

        self::assertSame(200, $response->status);
        self::assertSame('admin', $response->body);
    }

    private function session(): Session
    {
        $data = [];

        return new Session(state: new SessionState(data: $data), runtime: new NativeSessionRuntime());
    }

    private function guard(Session $session): CmsGuard
    {
        return new CmsGuard(session: $session, users: new FixedCurrentCmsUserQuery(
            user: new CurrentCmsUser(id: 1, email: 'admin@example.com', name: 'Admin', role: 'admin'),
        ));
    }
}

final readonly class FixedCurrentCmsUserQuery implements ICurrentCmsUserQuery
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
