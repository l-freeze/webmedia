<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\User;

interface IUserRepository
{
    public function findByEmail(string $email): ?User;
}
