<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Update\Port;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;
}
