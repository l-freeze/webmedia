<?php

declare(strict_types=1);

namespace Tests\Cms\Auth;

use App\Cms\Application\Auth\Login\Input;
use App\Cms\Application\Auth\Login\Interactor;
use App\Cms\Application\Auth\Login\Port\IPasswordVerifier;
use App\Cms\Application\Auth\Login\Query\ILoginAttemptQuery;
use App\Cms\Application\Auth\Login\Value\LoginAttemptFailureCounts;
use App\Cms\Domain\Entity\LoginAttempt;
use App\Cms\Domain\Entity\User;
use App\Cms\Domain\Repository\ILoginAttemptRepository;
use App\Cms\Domain\Repository\IUserRepository;
use App\Cms\Domain\Value\AdminUserEmail;
use App\Cms\Presentation\Http\Controller\Auth\Login\Controller;
use Pera\Framework\Http\Request;
use Pera\Framework\Security\Csrf;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use Pera\Framework\Security\SessionState;
use Pera\Framework\View\ITemplateRenderer;
use App\Shared\Event\Application\IEventBus;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Shared\Time\FrozenClock;

final class LoginControllerTest extends TestCase
{
    public function testBlockedLoginRecordsSecurityLogAndShowsGenericFailureMessage(): void
    {
        $sessionData = [];
        $session = new Session(state: new SessionState(data: $sessionData), runtime: new NativeSessionRuntime());
        $renderer = new CapturingLoginRenderer();
        $events = new RecordingLoginEventBus();
        $controller = new Controller(
            renderer: $renderer,
            csrf: new Csrf(session: $session),
            session: $session,
            login: new Interactor(
                users: new EmptyLoginUserRepository(),
                passwordVerifier: new NeverCalledPasswordVerifier(),
                loginAttemptQuery: new ControllerLoginAttemptQuery(new LoginAttemptFailureCounts(byEmailAndIp: 5, byIp: 0)),
                loginAttempts: new ControllerLoginAttemptRepository(),
                clock: new FrozenClock(new DateTimeImmutable('2026-07-13 00:00:00 UTC')),
            ),
            events: $events,
        );

        $response = $controller(new Request(
            method: 'POST',
            path: '/admin/login',
            query: [],
            body: ['email' => 'admin@example.test', 'password' => 'secret'],
            server: ['REMOTE_ADDR' => '192.0.2.10', 'HTTP_USER_AGENT' => 'PHPUnit Browser'],
        ));

        self::assertSame(422, $response->status);
        self::assertSame('auth/login.latte', $renderer->template);
        self::assertSame('ログインに失敗しました。時間をおいて再度お試しください。', $renderer->params['error'] ?? null);
        self::assertCount(1, $events->events);

        $event = $events->events[0];
        self::assertInstanceOf(SecurityLogEvent::class, $event);
        self::assertSame('cms.login', $event->eventType);
        self::assertSame('blocked', $event->result);
        self::assertSame('192.0.2.10', $event->ipAddress);
    }
}

final class CapturingLoginRenderer implements ITemplateRenderer
{
    public ?string $template = null;

    /** @var array<string, mixed> */
    public array $params = [];

    /** @param array<string, mixed> $params */
    public function render(string $template, array $params = []): string
    {
        $this->template = $template;
        $this->params = $params;

        return 'rendered-login';
    }
}

final class RecordingLoginEventBus implements IEventBus
{
    /** @var list<object> */
    public array $events = [];

    public function dispatch(object $event): void
    {
        $this->events[] = $event;
    }
}

final readonly class EmptyLoginUserRepository implements IUserRepository
{
    public function findByEmail(AdminUserEmail $email): ?User
    {
        return null;
    }
}

final readonly class NeverCalledPasswordVerifier implements IPasswordVerifier
{
    public function verify(string $plainPassword, string $passwordHash): bool
    {
        throw new \RuntimeException('Password verifier should not be called for blocked attempts.');
    }
}

final readonly class ControllerLoginAttemptQuery implements ILoginAttemptQuery
{
    public function __construct(private LoginAttemptFailureCounts $counts)
    {
    }

    public function countFailures(string $email, string $ipAddress, DateTimeImmutable $since): LoginAttemptFailureCounts
    {
        return $this->counts;
    }
}

final class ControllerLoginAttemptRepository implements ILoginAttemptRepository
{
    public ?LoginAttempt $lastAttempt = null;

    public function save(LoginAttempt $attempt): void
    {
        $this->lastAttempt = $attempt;
    }
}
