<?php

declare(strict_types=1);

namespace App\Framework\Security;

final class Csrf
{
    public function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public function validate(string $token): bool
    {
        return isset($_SESSION['_csrf']) && hash_equals((string) $_SESSION['_csrf'], $token);
    }
}
