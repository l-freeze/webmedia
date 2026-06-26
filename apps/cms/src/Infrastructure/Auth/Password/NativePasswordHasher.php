<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Auth\Password;

use App\Cms\Application\Auth\Password\PasswordHasher;

final readonly class NativePasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return password_hash(password: $plainPassword, algo: PASSWORD_DEFAULT);
    }
}
