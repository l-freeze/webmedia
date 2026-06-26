<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Password;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;
}
