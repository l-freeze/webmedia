<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Login;

use App\Cms\Application\Auth\Login\Query\ILoginAttemptQuery;
use App\Cms\Domain\Entity\LoginAttempt;
use App\Cms\Domain\Entity\User;
use App\Cms\Domain\Repository\ILoginAttemptRepository;
use App\Cms\Application\Auth\Login\Port\IPasswordVerifier;
use App\Cms\Domain\Repository\IUserRepository;
use App\Shared\Time\Application\IClock;
use DateTimeImmutable;

final readonly class Interactor
{
    private const ATTEMPT_WINDOW_SECONDS = 600;
    private const MAX_FAILURES_PER_EMAIL_AND_IP = 5;
    private const MAX_FAILURES_PER_IP = 20;

    public function __construct(
        private IUserRepository $users,
        private IPasswordVerifier $passwordVerifier,
        private ILoginAttemptQuery $loginAttemptQuery,
        private ILoginAttemptRepository $loginAttempts,
        private IClock $clock,
    ) {
    }

    public function execute(Input $input): Output
    {
        $now = $this->clock->now();
        $blockedByRateLimit = $this->hasTooManyFailedLoginAttempts($input, $now);
        if ($blockedByRateLimit) {
            $loginAttempt = LoginAttempt::blocked(
                email: $input->email,
                ipAddress: $input->ipAddress,
                attemptedAt: $now,
            );
            $this->loginAttempts->save($loginAttempt);

            return Output::blocked();
        }

        $user = $this->findUserByValidCredentials($input);
        if ($user === null) {
            $loginAttempt = LoginAttempt::failure(
                email: $input->email,
                ipAddress: $input->ipAddress,
                attemptedAt: $now,
            );
            $this->loginAttempts->save($loginAttempt);

            return Output::failed();
        }

        $loginAttempt = LoginAttempt::success(
            email: $input->email,
            ipAddress: $input->ipAddress,
            attemptedAt: $now,
        );
        $this->loginAttempts->save($loginAttempt);

        return Output::authenticated(userId: $user->id, role: $user->role->value);
    }

    private function findUserByValidCredentials(Input $input): ?User
    {
        $user = $this->users->findByEmail($input->email);
        if ($user === null) {
            return null;
        }

        $validPassword = $this->passwordVerifier->verify(
            plainPassword: $input->password,
            passwordHash: $user->passwordHash,
        );
        if (!$validPassword) {
            return null;
        }

        return $user;
    }

    private function hasTooManyFailedLoginAttempts(Input $input, DateTimeImmutable $now): bool
    {
        $since = $now->modify('-' . self::ATTEMPT_WINDOW_SECONDS . ' seconds');
        $failureCounts = $this->loginAttemptQuery->countFailures(
            email: $input->email,
            ipAddress: $input->ipAddress,
            since: $since,
        );

        return $failureCounts->byEmailAndIp >= self::MAX_FAILURES_PER_EMAIL_AND_IP
            || $failureCounts->byIp >= self::MAX_FAILURES_PER_IP;
    }
}
