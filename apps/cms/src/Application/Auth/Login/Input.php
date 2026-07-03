<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Login;

final readonly class Input
{
    public function __construct(
        public string $email,
        public string $password,
        public string $ipAddress,
    ) {
    }
}
