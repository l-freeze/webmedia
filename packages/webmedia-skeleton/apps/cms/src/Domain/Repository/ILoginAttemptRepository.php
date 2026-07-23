<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\LoginAttempt;

interface ILoginAttemptRepository
{
    public function save(LoginAttempt $attempt): void;
}
