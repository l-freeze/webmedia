<?php

declare(strict_types=1);

namespace Tests\Cms\Http\Middleware;

use App\Cms\Application\Auth\Authorization\AuthorizationService;
use App\Cms\Application\Auth\Authorization\CodeDefinedRolePermissionQuery;
use App\Cms\Application\Auth\Authorization\Permission;
use App\Cms\Application\Auth\Authorization\Role;
use App\Cms\Application\Auth\CurrentUser\Query\ICurrentCmsUserQuery;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;
use App\Cms\Infrastructure\Http\Middleware\RequireCmsPermissionMiddleware;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Middleware\CallableRequestHandler;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Routing\Router;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use App\Shared\SecurityLog\Application\SecurityLoggerInterface;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;
use PHPUnit\Framework\TestCase;

final class RequireCmsPermissionMiddlewareTest extends TestCase
{
    public function testAllowsRequestWithoutPermissionMetadata(): void
    {
        $middleware = $this->middleware(user: $this->user(role: Role::VIEWER));

        $response = $middleware->process(
            request: new Request(method: 'GET', path: '/admin/articles', query: [], body: [], server: []),
            handler: $this->okHandler(),
        );

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body);
    }

    public function testRedirectsWhenPermissionMetadataRequiresLoginButUserIsMissing(): void
    {
        $securityLogger = new RecordingSecurityLogger();
        $middleware = $this->middleware(user: null, securityLogger: $securityLogger);

        $response = $middleware->process(
            request: $this->requestWithPermission(permission: Permission::ARTICLE_UPDATE),
            handler: $this->okHandler(),
        );

        self::assertSame(302, $response->status);
        self::assertSame('/admin/login', $response->headers['Location'] ?? null);
        self::assertCount(1, $securityLogger->events);
        self::assertSame('cms.authorization.unauthenticated', $securityLogger->events[0]->eventType);
        self::assertSame(Permission::ARTICLE_UPDATE, $securityLogger->events[0]->metadata['permission'] ?? null);
    }

    public function testReturnsForbiddenWhenUserDoesNotHaveRequiredPermission(): void
    {
        $securityLogger = new RecordingSecurityLogger();
        $middleware = $this->middleware(user: $this->user(role: Role::WRITER), securityLogger: $securityLogger);

        $response = $middleware->process(
            request: $this->requestWithPermission(permission: Permission::SETTING_UPDATE),
            handler: $this->okHandler(),
        );

        self::assertSame(403, $response->status);
        self::assertSame('Forbidden', $response->body);
        self::assertCount(1, $securityLogger->events);
        self::assertSame('cms.authorization.denied', $securityLogger->events[0]->eventType);
        self::assertSame('failure', $securityLogger->events[0]->result);
        self::assertSame('1', $securityLogger->events[0]->actorUserId);
        self::assertSame(Role::WRITER, $securityLogger->events[0]->actorRole);
        self::assertSame(Permission::SETTING_UPDATE, $securityLogger->events[0]->metadata['permission'] ?? null);
    }

    public function testAllowsUserWithRequiredPermission(): void
    {
        $middleware = $this->middleware(user: $this->user(role: Role::ADMIN));

        $response = $middleware->process(
            request: $this->requestWithPermission(permission: Permission::SETTING_UPDATE),
            handler: $this->okHandler(),
        );

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body);
    }

    private function middleware(?CurrentCmsUser $user, ?RecordingSecurityLogger $securityLogger = null): RequireCmsPermissionMiddleware
    {
        $data = ['cms.user_id' => 1];
        $session = new Session(state: new SessionState(data: $data), runtime: new NativeSessionRuntime());

        return new RequireCmsPermissionMiddleware(
            guard: new CmsGuard(session: $session, users: new FixedPermissionCmsUserQuery(user: $user)),
            authorization: new AuthorizationService(rolePermissions: new CodeDefinedRolePermissionQuery()),
            securityLogger: $securityLogger ?? new RecordingSecurityLogger(),
        );
    }

    private function requestWithPermission(string $permission): Request
    {
        return (new Request(method: 'POST', path: '/admin/settings', query: [], body: [], server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit Browser',
            'HTTP_X_REQUEST_ID' => 'request-123',
        ]))
            ->withAttribute(key: Router::ROUTE_METADATA_ATTRIBUTE, value: ['requires_permission' => $permission]);
    }

    private function okHandler(): CallableRequestHandler
    {
        return new CallableRequestHandler(
            handler: static fn (Request $request): Response => new Response(body: 'ok'),
        );
    }

    private function user(string $role): CurrentCmsUser
    {
        return new CurrentCmsUser(id: 1, email: 'user@example.test', name: 'CMS User', role: $role);
    }
}

final readonly class FixedPermissionCmsUserQuery implements ICurrentCmsUserQuery
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

final class RecordingSecurityLogger implements SecurityLoggerInterface
{
    /** @var list<SecurityLogEvent> */
    public array $events = [];

    public function record(SecurityLogEvent $event): void
    {
        $this->events[] = $event;
    }
}
