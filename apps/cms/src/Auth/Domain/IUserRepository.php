<?php

declare(strict_types=1);

namespace App\Cms\Auth\Domain;

use App\Cms\Auth\Domain\Entity\User;

interface IUserRepository
{
    public function findByEmail(string $email): ?User;
}
