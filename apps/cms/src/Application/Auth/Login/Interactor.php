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
        $failureCounts = $this->loginAttemptQuery->countFailures(
            email: $input->email,
            ipAddress: $input->ipAddress,
            since: $this->loginAttemptWindowStart($now),
        );
        if ($this->tooManyLoginAttempts($failureCounts)) {
            $this->loginAttempts->save(LoginAttempt::blocked(
                email: $input->email,
                ipAddress: $input->ipAddress,
                attemptedAt: $now,
            ));

            return Output::blocked();
        }

        $user = $this->users->findByEmail($input->email);
        if ($user === null || !$this->passwordVerifier->verify(
            plainPassword: $input->password,
            passwordHash: $user->passwordHash,
        )) {
            $this->loginAttempts->save(LoginAttempt::failure(
                email: $input->email,
                ipAddress: $input->ipAddress,
                attemptedAt: $now,
            ));

            return Output::failed();
        }

        $this->loginAttempts->save(LoginAttempt::success(
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
        return $failureCounts->byEmailAndIp >= self::MAX_FAILURES_PER_EMAIL_AND_IP
            || $failureCounts->byIp >= self::MAX_FAILURES_PER_IP;
    }
}
