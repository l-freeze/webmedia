<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\CmsLoginAttemptReset;

interface ICmsLoginAttemptResetRepository
{
    public function save(CmsLoginAttemptReset $reset): void;
}
