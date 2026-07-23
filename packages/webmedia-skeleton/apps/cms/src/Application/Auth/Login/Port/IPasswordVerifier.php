<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Login\Port;

interface IPasswordVerifier
{
    public function verify(string $plainPassword, string $passwordHash): bool;
}
