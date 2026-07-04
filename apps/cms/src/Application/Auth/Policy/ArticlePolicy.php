<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Policy;

use App\Cms\Application\Auth\Policy\Value\ArticlePolicyArticle;
use App\Cms\Application\Auth\Policy\AuthorizationService;
use App\Cms\Application\Auth\Policy\Value\PermissionCode;
use App\Cms\Application\Auth\Policy\Value\RoleCode;
use App\Cms\Application\Auth\Policy\RoleHierarchy;
use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;

final readonly class ArticlePolicy
{
    public function __construct(
        private AuthorizationService $authorization,
        private RoleHierarchy $roles,
    ) {
    }

    public function canCreate(CurrentCmsUser $user): bool
    {
        return $this->authorization->can(user: $user, permission: PermissionCode::ARTICLE_CREATE);
    }

    public function canUpdate(CurrentCmsUser $user, ArticlePolicyArticle $article): bool
    {
        if (!$this->authorization->can(user: $user, permission: PermissionCode::ARTICLE_UPDATE)) {
            return false;
        }

        if ($this->roles->isAtLeast(actualRole: $user->role, requiredRole: RoleCode::EDITOR)) {
            return true;
        }

        return $article->authorId === $user->id;
    }

    public function canPublish(CurrentCmsUser $user): bool
    {
        return $this->authorization->can(user: $user, permission: PermissionCode::ARTICLE_PUBLISH);
    }

    public function canDelete(CurrentCmsUser $user): bool
    {
        return $this->authorization->can(user: $user, permission: PermissionCode::ARTICLE_DELETE);
    }

    public function canRestore(CurrentCmsUser $user): bool
    {
        return $this->authorization->can(user: $user, permission: PermissionCode::ARTICLE_RESTORE);
    }
}
