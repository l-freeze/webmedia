<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\List;

use App\Cms\Application\AdminUser\List\Query\IAdminUserQuery;

final readonly class Interactor
{
    public function __construct(private IAdminUserQuery $users)
    {
    }

    public function execute(Input $input): Output
    {
        $users = $this->users->users();

        return new Output(users: $users);
    }
}
