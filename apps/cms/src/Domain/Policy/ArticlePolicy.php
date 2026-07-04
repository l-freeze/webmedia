<?php

declare(strict_types=1);

namespace App\Cms\Domain\Policy;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Exception\ResourceOperationDeniedException;
use App\Cms\Domain\Value\ArticleCreatePermission;
use App\Cms\Domain\Value\ArticleDeletePermission;
use App\Cms\Domain\Value\ArticlePublishPermission;
use App\Cms\Domain\Value\ArticleRestorePermission;
use App\Cms\Domain\Value\ArticleUpdatePermission;
use App\Cms\Domain\Value\CmsActor;
use App\Cms\Domain\Value\CmsRole;

final readonly class ArticlePolicy
{
    public function authorizeCreate(CmsActor $actor): ArticleCreatePermission
    {
        if (!CmsRole::isAtLeast(actualRole: $actor->role, requiredRole: CmsRole::WRITER)) {
            throw new ResourceOperationDeniedException(actorId: $actor->id, operation: 'article.create');
        }

        return new ArticleCreatePermission(actor: $actor);
    }

    public function authorizeUpdate(CmsActor $actor, Article $article): ArticleUpdatePermission
    {
        if (CmsRole::isAtLeast(actualRole: $actor->role, requiredRole: CmsRole::EDITOR)) {
            return new ArticleUpdatePermission(actor: $actor);
        }

        if ($actor->role === CmsRole::WRITER && $article->authorId === $actor->id) {
            return new ArticleUpdatePermission(actor: $actor);
        }

        throw new ResourceOperationDeniedException(actorId: $actor->id, operation: 'article.update');
    }

    public function authorizePublish(CmsActor $actor): ArticlePublishPermission
    {
        if (!CmsRole::isAtLeast(actualRole: $actor->role, requiredRole: CmsRole::EDITOR)) {
            throw new ResourceOperationDeniedException(actorId: $actor->id, operation: 'article.publish');
        }

        return new ArticlePublishPermission(actor: $actor);
    }

    public function authorizeDelete(CmsActor $actor): ArticleDeletePermission
    {
        if (!CmsRole::isAtLeast(actualRole: $actor->role, requiredRole: CmsRole::EDITOR)) {
            throw new ResourceOperationDeniedException(actorId: $actor->id, operation: 'article.delete');
        }

        return new ArticleDeletePermission(actor: $actor);
    }

    public function authorizeRestore(CmsActor $actor): ArticleRestorePermission
    {
        if (!CmsRole::isAtLeast(actualRole: $actor->role, requiredRole: CmsRole::EDITOR)) {
            throw new ResourceOperationDeniedException(actorId: $actor->id, operation: 'article.restore');
        }

        return new ArticleRestorePermission(actor: $actor);
    }
}
