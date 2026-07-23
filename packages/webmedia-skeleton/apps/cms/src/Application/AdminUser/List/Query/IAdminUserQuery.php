<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\List\Query;

use App\Cms\Application\AdminUser\List\Value\AdminUser;

interface IAdminUserQuery
{
    /** @return list<AdminUser> */
    public function users(): array;
}
