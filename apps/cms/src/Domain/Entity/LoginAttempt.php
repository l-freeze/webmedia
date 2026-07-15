<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\IpAddress;
use App\Cms\Domain\Value\LoginAttemptedAt;
use App\Cms\Domain\Value\LoginAttemptEmail;
use App\Cms\Domain\Value\LoginAttemptResult;

final readonly class LoginAttempt
{
    private function __construct(
        public LoginAttemptEmail $email,
        public IpAddress $ipAddress,
        public LoginAttemptResult $result,
        public LoginAttemptedAt $attemptedAt,
    ) {
    }

    public static function success(LoginAttemptEmail $email, IpAddress $ipAddress, LoginAttemptedAt $attemptedAt): self
    {
        return new self(
            email: $email,
            ipAddress: $ipAddress,
            result: LoginAttemptResult::Success,
            attemptedAt: $attemptedAt,
        );
    }

    public static function failure(LoginAttemptEmail $email, IpAddress $ipAddress, LoginAttemptedAt $attemptedAt): self
    {
        return new self(
            email: $email,
            ipAddress: $ipAddress,
            result: LoginAttemptResult::Failure,
            attemptedAt: $attemptedAt,
        );
    }

    public static function blocked(LoginAttemptEmail $email, IpAddress $ipAddress, LoginAttemptedAt $attemptedAt): self
    {
        return new self(
            email: $email,
            ipAddress: $ipAddress,
            result: LoginAttemptResult::Blocked,
            attemptedAt: $attemptedAt,
        );
    }
}
