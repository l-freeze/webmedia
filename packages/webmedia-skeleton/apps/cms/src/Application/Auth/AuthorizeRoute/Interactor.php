<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\AuthorizeRoute;

use App\Cms\Application\Auth\AuthorizeRoute\Query\IRolePermissionQuery;

final readonly class Interactor
{
    public function __construct(private IRolePermissionQuery $rolePermissions)
    {
    }

    public function execute(Input $input): Output
    {
        $allowed = $this->rolePermissions->hasPermission(
            role: $input->actorRole,
            permission: $input->requiredPermission,
        );

        return new Output(allowed: $allowed);
    }
}
