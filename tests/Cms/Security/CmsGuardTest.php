<?php

declare(strict_types=1);

namespace Tests\Cms\Security;

use App\Cms\Application\Auth\GetCurrentUser\Query\ICurrentCmsUserQuery;
use App\Cms\Application\Auth\GetCurrentUser\Interactor;
use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use PHPUnit\Framework\TestCase;

final class CmsGuardTest extends TestCase
{
    public function testReturnsNullWithoutUserIdInSession(): void
    {
        $query = new RecordingCurrentCmsUserQuery(user: $this->user());
        $guard = new CmsGuard(session: $this->session(), currentUsers: new Interactor(users: $query));

        self::assertNull($guard->currentUser());
        self::assertFalse($guard->isAuthenticated());
        self::assertSame([], $query->ids);
    }

    public function testReturnsNullForInvalidUserIdInSession(): void
    {
        $session = $this->session();
        $session->set(key: 'cms.user_id', value: 'abc');
        $query = new RecordingCurrentCmsUserQuery(user: $this->user());
        $guard = new CmsGuard(session: $session, currentUsers: new Interactor(users: $query));

        self::assertNull($guard->currentUser());
        self::assertFalse($guard->isAuthenticated());
        self::assertSame([], $query->ids);
    }

    public function testReturnsNullWhenSessionUserNoLongerExists(): void
    {
        $session = $this->session();
        $session->set(key: 'cms.user_id', value: 9);
        $query = new RecordingCurrentCmsUserQuery(user: null);
        $guard = new CmsGuard(session: $session, currentUsers: new Interactor(users: $query));

        self::assertNull($guard->currentUser());
        self::assertFalse($guard->isAuthenticated());
        self::assertSame([9], $query->ids);
    }

    public function testReturnsCurrentUserForValidSessionUserId(): void
    {
        $session = $this->session();
        $session->set(key: 'cms.user_id', value: '1');
        $query = new RecordingCurrentCmsUserQuery(user: $this->user());
        $guard = new CmsGuard(session: $session, currentUsers: new Interactor(users: $query));

        $user = $guard->currentUser();

        self::assertInstanceOf(CurrentCmsUser::class, $user);
        self::assertSame(1, $user->id);
        self::assertSame('admin@example.com', $user->email);
        self::assertSame('Admin', $user->name);
        self::assertSame('admin', $user->role);
        self::assertTrue($guard->isAuthenticated());
        self::assertSame([1], $query->ids);
    }

    private function user(): CurrentCmsUser
    {
        return new CurrentCmsUser(id: 1, email: 'admin@example.com', name: 'Admin', role: 'admin');
    }

    private function session(): Session
    {
        $data = [];

        return new Session(state: new SessionState(data: $data), runtime: new NativeSessionRuntime());
    }
}

final class RecordingCurrentCmsUserQuery implements ICurrentCmsUserQuery
{
    /** @var array<int, int> */
    public array $ids = [];

    public function __construct(private readonly ?CurrentCmsUser $user)
    {
    }

    public function findById(int $id): ?CurrentCmsUser
    {
        $this->ids[] = $id;
        if ($this->user === null || $this->user->id !== $id) {
            return null;
        }

        return $this->user;
    }
}
