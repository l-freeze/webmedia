<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Policy;

use App\Cms\Application\Article\Policy\Value\ArticlePolicyArticle;
use App\Cms\Application\Auth\Authorization\AuthorizationService;
use App\Cms\Application\Auth\Authorization\Permission;
use App\Cms\Application\Auth\Authorization\Role;
use App\Cms\Application\Auth\Authorization\RoleHierarchy;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;

final readonly class ArticlePolicy
{
    public function __construct(
        private AuthorizationService $authorization,
        private RoleHierarchy $roles,
    ) {
    }

    public function canCreate(CurrentCmsUser $user): bool
    {
        return $this->authorization->can(user: $user, permission: Permission::ARTICLE_CREATE);
    }

    public function canUpdate(CurrentCmsUser $user, ArticlePolicyArticle $article): bool
    {
        if (!$this->authorization->can(user: $user, permission: Permission::ARTICLE_UPDATE)) {
            return false;
        }

        if ($this->roles->isAtLeast(actualRole: $user->role, requiredRole: Role::EDITOR)) {
            return true;
        }

        return $article->authorId === $user->id;
    }

    public function canPublish(CurrentCmsUser $user): bool
    {
        return $this->authorization->can(user: $user, permission: Permission::ARTICLE_PUBLISH);
    }

    public function canDelete(CurrentCmsUser $user): bool
    {
        return $this->authorization->can(user: $user, permission: Permission::ARTICLE_DELETE);
    }

    public function canRestore(CurrentCmsUser $user): bool
    {
        return $this->authorization->can(user: $user, permission: Permission::ARTICLE_RESTORE);
    }
}
