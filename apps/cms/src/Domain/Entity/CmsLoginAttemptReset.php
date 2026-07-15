<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\CmsLoginAttemptResetScope;
use App\Cms\Domain\Value\IpAddress;
use App\Cms\Domain\Value\LoginAttemptEmail;
use App\Cms\Domain\Value\LoginAttemptResetAt;
use App\Cms\Domain\Value\LoginAttemptResetReason;

final readonly class CmsLoginAttemptReset
{
    private function __construct(
        public CmsLoginAttemptResetScope $scope,
        public ?LoginAttemptEmail $email,
        public IpAddress $ipAddress,
        public LoginAttemptResetAt $resetAt,
        public AdminUserId $resetByUserId,
        public LoginAttemptResetReason $reason,
    ) {
    }

    public static function emailIp(
        LoginAttemptEmail $email,
        IpAddress $ipAddress,
        LoginAttemptResetAt $resetAt,
        AdminUserId $resetByUserId,
        LoginAttemptResetReason $reason,
    ): self
    {
        return new self(
            scope: CmsLoginAttemptResetScope::EmailIp,
            email: $email,
            ipAddress: $ipAddress,
            resetAt: $resetAt,
            resetByUserId: $resetByUserId,
            reason: $reason,
        );
    }

    public static function ip(
        IpAddress $ipAddress,
        LoginAttemptResetAt $resetAt,
        AdminUserId $resetByUserId,
        LoginAttemptResetReason $reason,
    ): self
    {
        return new self(
            scope: CmsLoginAttemptResetScope::Ip,
            email: null,
            ipAddress: $ipAddress,
            resetAt: $resetAt,
            resetByUserId: $resetByUserId,
            reason: $reason,
        );
    }
}
