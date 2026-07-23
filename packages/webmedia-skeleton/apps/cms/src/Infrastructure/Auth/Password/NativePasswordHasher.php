<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Auth\Password;

use App\Cms\Application\AdminUser\Create\Port\IPasswordHasher as CreatePasswordHasher;
use App\Cms\Application\AdminUser\Update\Port\IPasswordHasher as UpdatePasswordHasher;

final readonly class NativePasswordHasher implements CreatePasswordHasher, UpdatePasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return password_hash(password: $plainPassword, algo: PASSWORD_DEFAULT);
    }
}
