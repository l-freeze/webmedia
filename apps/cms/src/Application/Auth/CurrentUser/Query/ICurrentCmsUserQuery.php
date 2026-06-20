<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\CurrentUser\Query;

use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;

interface ICurrentCmsUserQuery
{
    public function findById(int $id): ?CurrentCmsUser;
}
