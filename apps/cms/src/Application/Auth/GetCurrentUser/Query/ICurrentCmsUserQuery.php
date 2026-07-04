<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\GetCurrentUser\Query;

use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;

interface ICurrentCmsUserQuery
{
    public function findById(int $id): ?CurrentCmsUser;
}
