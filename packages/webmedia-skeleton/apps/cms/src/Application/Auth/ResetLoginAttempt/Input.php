<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\ResetLoginAttempt;

final readonly class Input
{
    private function __construct(
        public string $scope,
        public string $email,
        public string $ipAddress,
        public int $resetByUserId,
        public string $reason,
    ) {
    }

    public static function create(string $scope, string $email, string $ipAddress, int $resetByUserId, string $reason): self
    {
        return new self(
            scope: trim($scope),
            email: trim($email),
            ipAddress: trim($ipAddress),
            resetByUserId: $resetByUserId,
            reason: trim($reason),
        );
    }
}
