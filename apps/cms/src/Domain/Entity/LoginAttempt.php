<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\LoginAttemptResult;
use DateTimeImmutable;

final readonly class LoginAttempt
{
    private function __construct(
        public string $email,
        public string $ipAddress,
        public LoginAttemptResult $result,
        public DateTimeImmutable $attemptedAt,
    ) {
    }

    public static function success(string $email, string $ipAddress, DateTimeImmutable $attemptedAt): self
    {
        return new self(
            email: $email,
            ipAddress: $ipAddress,
            result: LoginAttemptResult::Success,
            attemptedAt: $attemptedAt,
        );
    }

    public static function failure(string $email, string $ipAddress, DateTimeImmutable $attemptedAt): self
    {
        return new self(
            email: $email,
            ipAddress: $ipAddress,
            result: LoginAttemptResult::Failure,
            attemptedAt: $attemptedAt,
        );
    }

    public static function blocked(string $email, string $ipAddress, DateTimeImmutable $attemptedAt): self
    {
        return new self(
            email: $email,
            ipAddress: $ipAddress,
            result: LoginAttemptResult::Blocked,
            attemptedAt: $attemptedAt,
        );
    }
}
