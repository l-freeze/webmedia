<?php

declare(strict_types=1);

namespace Tests\Cms\Auth;

use App\Cms\Application\Auth\Login\Input;
use App\Cms\Application\Auth\Login\Interactor;
use App\Cms\Application\Auth\Login\Port\PasswordVerifier;
use App\Cms\Application\Auth\Login\Query\ILoginAttemptQuery;
use App\Cms\Application\Auth\Login\Value\LoginAttemptFailureCounts;
use App\Cms\Domain\Entity\LoginAttempt;
use App\Cms\Domain\Repository\ILoginAttemptRepository;
use App\Cms\Domain\Entity\User;
use App\Cms\Domain\Repository\IUserRepository;
use App\Cms\Domain\Value\LoginAttemptResult;
use App\Cms\Infrastructure\Auth\Password\NativePasswordVerifier;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LoginInteractorTest extends TestCase
{
    public function testAuthenticatesUserThroughPasswordVerifierPort(): void
    {
        $attempts = new RecordingLoginAttemptRepository();
        $interactor = new Interactor(
            users: new InMemoryUserRepository($this->user()),
            passwordVerifier: new FixedPasswordVerifier(matches: true),
            loginAttemptQuery: new FixedLoginAttemptQuery(new LoginAttemptFailureCounts(emailAndIp: 0, ip: 0)),
            loginAttempts: $attempts,
        );

        $output = $interactor->execute(input: new Input(
            email: 'admin@example.test',
            password: 'secret',
            ipAddress: '192.0.2.10',
        ));

        self::assertTrue($output->authenticated);
        self::assertSame(10, $output->userId);
        self::assertSame('admin', $output->role);
        self::assertSame(LoginAttemptResult::Success, $attempts->lastAttempt?->result);
    }

    public function testRejectsWhenPasswordVerifierRejectsPassword(): void
    {
        $attempts = new RecordingLoginAttemptRepository();
        $interactor = new Interactor(
            users: new InMemoryUserRepository($this->user()),
            passwordVerifier: new FixedPasswordVerifier(matches: false),
            loginAttemptQuery: new FixedLoginAttemptQuery(new LoginAttemptFailureCounts(emailAndIp: 0, ip: 0)),
            loginAttempts: $attempts,
        );

        $output = $interactor->execute(input: new Input(
            email: 'admin@example.test',
            password: 'wrong',
            ipAddress: '192.0.2.10',
        ));

        self::assertFalse($output->authenticated);
        self::assertNull($output->userId);
        self::assertNull($output->role);
        self::assertFalse($output->blocked);
        self::assertSame(LoginAttemptResult::Failure, $attempts->lastAttempt?->result);
    }

    public function testRejectsWhenUserDoesNotExist(): void
    {
        $attempts = new RecordingLoginAttemptRepository();
        $interactor = new Interactor(
            users: new InMemoryUserRepository(null),
            passwordVerifier: new FixedPasswordVerifier(matches: true),
            loginAttemptQuery: new FixedLoginAttemptQuery(new LoginAttemptFailureCounts(emailAndIp: 0, ip: 0)),
            loginAttempts: $attempts,
        );

        $output = $interactor->execute(input: new Input(
            email: 'missing@example.test',
            password: 'secret',
            ipAddress: '192.0.2.10',
        ));

        self::assertFalse($output->authenticated);
        self::assertSame(LoginAttemptResult::Failure, $attempts->lastAttempt?->result);
    }

    public function testBlocksWhenEmailAndIpFailuresReachLimit(): void
    {
        $attempts = new RecordingLoginAttemptRepository();
        $passwordVerifier = new CountingPasswordVerifier(matches: true);
        $interactor = new Interactor(
            users: new InMemoryUserRepository($this->user()),
            passwordVerifier: $passwordVerifier,
            loginAttemptQuery: new FixedLoginAttemptQuery(new LoginAttemptFailureCounts(emailAndIp: 5, ip: 0)),
            loginAttempts: $attempts,
        );

        $output = $interactor->execute(input: new Input(
            email: 'admin@example.test',
            password: 'secret',
            ipAddress: '192.0.2.10',
        ));

        self::assertFalse($output->authenticated);
        self::assertTrue($output->blocked);
        self::assertSame(0, $passwordVerifier->calls);
        self::assertSame(LoginAttemptResult::Blocked, $attempts->lastAttempt?->result);
    }

    public function testBlocksWhenIpFailuresReachLimit(): void
    {
        $attempts = new RecordingLoginAttemptRepository();
        $interactor = new Interactor(
            users: new InMemoryUserRepository($this->user()),
            passwordVerifier: new FixedPasswordVerifier(matches: true),
            loginAttemptQuery: new FixedLoginAttemptQuery(new LoginAttemptFailureCounts(emailAndIp: 0, ip: 20)),
            loginAttempts: $attempts,
        );

        $output = $interactor->execute(input: new Input(
            email: 'admin@example.test',
            password: 'secret',
            ipAddress: '192.0.2.10',
        ));

        self::assertFalse($output->authenticated);
        self::assertTrue($output->blocked);
        self::assertSame(LoginAttemptResult::Blocked, $attempts->lastAttempt?->result);
    }

    public function testNativePasswordVerifierUsesPhpPasswordHash(): void
    {
        $verifier = new NativePasswordVerifier();
        $hash = password_hash(password: 'secret', algo: PASSWORD_DEFAULT);

        self::assertTrue($verifier->verify(plainPassword: 'secret', passwordHash: $hash));
        self::assertFalse($verifier->verify(plainPassword: 'wrong', passwordHash: $hash));
    }

    private function user(): User
    {
        return new User(
            id: 10,
            email: 'admin@example.test',
            passwordHash: 'hash-from-storage',
            name: 'Admin',
            role: 'admin',
        );
    }
}

final readonly class InMemoryUserRepository implements IUserRepository
{
    public function __construct(private ?User $user)
    {
    }

    public function findByEmail(string $email): ?User
    {
        if ($this->user === null || $this->user->email !== $email) {
            return null;
        }

        return $this->user;
    }
}

final readonly class FixedPasswordVerifier implements PasswordVerifier
{
    public function __construct(private bool $matches)
    {
    }

    public function verify(string $plainPassword, string $passwordHash): bool
    {
        return $this->matches;
    }
}

final class CountingPasswordVerifier implements PasswordVerifier
{
    public int $calls = 0;

    public function __construct(private bool $matches)
    {
    }

    public function verify(string $plainPassword, string $passwordHash): bool
    {
        $this->calls++;

        return $this->matches;
    }
}

final readonly class FixedLoginAttemptQuery implements ILoginAttemptQuery
{
    public function __construct(private LoginAttemptFailureCounts $counts)
    {
    }

    public function failureCounts(string $email, string $ipAddress, DateTimeImmutable $since): LoginAttemptFailureCounts
    {
        return $this->counts;
    }
}

final class RecordingLoginAttemptRepository implements ILoginAttemptRepository
{
    public ?LoginAttempt $lastAttempt = null;

    public function save(LoginAttempt $attempt): void
    {
        $this->lastAttempt = $attempt;
    }
}
