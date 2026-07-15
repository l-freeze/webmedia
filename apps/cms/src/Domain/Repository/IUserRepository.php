<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\User;
use App\Cms\Domain\Value\AdminUserEmail;

interface IUserRepository
{
    public function findByEmail(AdminUserEmail $email): ?User;
}
