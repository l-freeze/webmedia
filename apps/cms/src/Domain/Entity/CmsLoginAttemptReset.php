<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\CmsLoginAttemptResetScope;
use DateTimeImmutable;

final readonly class CmsLoginAttemptReset
{
    private function __construct(
        public CmsLoginAttemptResetScope $scope,
        public ?string $email,
        public string $ipAddress,
        public DateTimeImmutable $resetAt,
        public int $resetByUserId,
        public string $reason,
    ) {
    }

    public static function emailIp(string $email, string $ipAddress, DateTimeImmutable $resetAt, int $resetByUserId, string $reason): self
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

    public static function ip(string $ipAddress, DateTimeImmutable $resetAt, int $resetByUserId, string $reason): self
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
