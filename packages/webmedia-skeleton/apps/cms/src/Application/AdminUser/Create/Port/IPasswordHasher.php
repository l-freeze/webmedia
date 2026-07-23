<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Create\Port;

interface IPasswordHasher
{
    public function hash(string $plainPassword): string;
}
