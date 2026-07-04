<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\ResetLoginAttempt;

final readonly class Input
{
    public function __construct(
        public string $scope,
        public string $email,
        public string $ipAddress,
        public int $resetByUserId,
        public string $reason,
    ) {
    }
}
