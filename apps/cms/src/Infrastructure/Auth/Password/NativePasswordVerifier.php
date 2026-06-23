<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Auth\Password;

use App\Cms\Application\Auth\Login\PasswordVerifier;

final readonly class NativePasswordVerifier implements PasswordVerifier
{
    public function verify(string $plainPassword, string $passwordHash): bool
    {
        return password_verify(password: $plainPassword, hash: $passwordHash);
    }
}
