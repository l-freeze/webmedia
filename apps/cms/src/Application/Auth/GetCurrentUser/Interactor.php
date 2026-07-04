<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\GetCurrentUser;

use App\Cms\Application\Auth\GetCurrentUser\Query\ICurrentCmsUserQuery;

final readonly class Interactor
{
    public function __construct(private ICurrentCmsUserQuery $users)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(user: $this->users->findById($input->userId));
    }
}
