<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Login;

use App\Cms\Application\Auth\Login\Query\ILoginAttemptQuery;
use App\Cms\Application\Auth\Login\Value\LoginAttemptFailureCounts;
use App\Cms\Domain\Entity\LoginAttempt;
use App\Cms\Domain\Repository\ILoginAttemptRepository;
use App\Cms\Application\Auth\Login\Port\PasswordVerifier;
use App\Cms\Domain\Repository\IUserRepository;
use DateTimeImmutable;

final readonly class Interactor
{
    private const ATTEMPT_WINDOW_SECONDS = 600;
    private const MAX_FAILURES_PER_EMAIL_AND_IP = 5;
    private const MAX_FAILURES_PER_IP = 20;

    public function __construct(
        private IUserRepository $users,
        private PasswordVerifier $passwordVerifier,
        private ILoginAttemptQuery $loginAttemptQuery,
        private ILoginAttemptRepository $loginAttempts,
    ) {
    }

    public function execute(Input $input): Output
    {
        $now = new DateTimeImmutable();
        $failureCounts = $this->loginAttemptQuery->failureCounts(
            email: $input->email,
            ipAddress: $input->ipAddress,
            since: $this->loginAttemptWindowStart(now: $now),
        );
        if ($this->tooManyLoginAttempts(failureCounts: $failureCounts)) {
            $this->loginAttempts->save(attempt: LoginAttempt::blocked(
                email: $input->email,
                ipAddress: $input->ipAddress,
                attemptedAt: $now,
            ));

            return Output::blocked();
        }

        $user = $this->users->findByEmail(email: $input->email);
        if ($user === null || !$this->passwordVerifier->verify(
            plainPassword: $input->password,
            passwordHash: $user->passwordHash,
        )) {
            $this->loginAttempts->save(attempt: LoginAttempt::failure(
                email: $input->email,
                ipAddress: $input->ipAddress,
                attemptedAt: $now,
            ));

            return Output::failed();
        }

        $this->loginAttempts->save(attempt: LoginAttempt::success(
            email: $input->email,
            ipAddress: $input->ipAddress,
            attemptedAt: $now,
        ));

        return Output::authenticated(userId: $user->id, role: $user->role);
    }

    private function loginAttemptWindowStart(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify('-' . self::ATTEMPT_WINDOW_SECONDS . ' seconds');
    }

    private function tooManyLoginAttempts(LoginAttemptFailureCounts $failureCounts): bool
    {
        return $failureCounts->emailAndIp >= self::MAX_FAILURES_PER_EMAIL_AND_IP
            || $failureCounts->ip >= self::MAX_FAILURES_PER_IP;
    }
}
