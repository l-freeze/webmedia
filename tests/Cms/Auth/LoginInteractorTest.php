<?php

declare(strict_types=1);

namespace Tests\Cms\Auth;

use App\Cms\Application\Auth\Login\Input;
use App\Cms\Application\Auth\Login\Interactor;
use App\Cms\Application\Auth\Login\Port\PasswordVerifier;
use App\Cms\Domain\Entity\User;
use App\Cms\Domain\Repository\IUserRepository;
use App\Cms\Infrastructure\Auth\Password\NativePasswordVerifier;
use PHPUnit\Framework\TestCase;

final class LoginInteractorTest extends TestCase
{
    public function testAuthenticatesUserThroughPasswordVerifierPort(): void
    {
        $interactor = new Interactor(
            users: new InMemoryUserRepository($this->user()),
            passwordVerifier: new FixedPasswordVerifier(matches: true),
        );

        $output = $interactor->execute(input: new Input(
            email: 'admin@example.test',
            password: 'secret',
        ));

        self::assertTrue($output->authenticated);
        self::assertSame(10, $output->userId);
        self::assertSame('admin', $output->role);
    }

    public function testRejectsWhenPasswordVerifierRejectsPassword(): void
    {
        $interactor = new Interactor(
            users: new InMemoryUserRepository($this->user()),
            passwordVerifier: new FixedPasswordVerifier(matches: false),
        );

        $output = $interactor->execute(input: new Input(
            email: 'admin@example.test',
            password: 'wrong',
        ));

        self::assertFalse($output->authenticated);
        self::assertNull($output->userId);
        self::assertNull($output->role);
    }

    public function testRejectsWhenUserDoesNotExist(): void
    {
        $interactor = new Interactor(
            users: new InMemoryUserRepository(null),
            passwordVerifier: new FixedPasswordVerifier(matches: true),
        );

        $output = $interactor->execute(input: new Input(
            email: 'missing@example.test',
            password: 'secret',
        ));

        self::assertFalse($output->authenticated);
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
