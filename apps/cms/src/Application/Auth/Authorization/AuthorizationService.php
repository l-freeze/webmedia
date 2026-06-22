<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Authorization;

use App\Cms\Application\Auth\Authorization\Exception\AuthorizationException;
use App\Cms\Application\Auth\Authorization\Query\IRolePermissionQuery;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;

final readonly class AuthorizationService
{
    public function __construct(
        private IRolePermissionQuery $rolePermissions,
    ) {
    }

    public function can(CurrentCmsUser $user, string $permission): bool
    {
        return $this->rolePermissions->hasPermission(role: $user->role, permission: $permission);
    }

    public function authorize(CurrentCmsUser $user, string $permission): void
    {
        if (!$this->can(user: $user, permission: $permission)) {
            throw new AuthorizationException(userId: $user->id, permission: $permission);
        }
    }
}
