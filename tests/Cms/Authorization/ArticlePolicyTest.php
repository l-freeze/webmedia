<?php

declare(strict_types=1);

namespace Tests\Cms\Authorization;

use App\Cms\Application\Article\Policy\ArticlePolicy;
use App\Cms\Application\Article\Policy\Value\ArticlePolicyArticle;
use App\Cms\Application\Auth\Authorization\AuthorizationService;
use App\Cms\Application\Auth\Authorization\Role;
use App\Cms\Application\Auth\Authorization\RoleHierarchy;
use App\Cms\Application\Auth\Authorization\RolePermissions;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;
use PHPUnit\Framework\TestCase;

final class ArticlePolicyTest extends TestCase
{
    public function testWriterCanUpdateOwnArticleOnly(): void
    {
        $policy = $this->policy();
        $writer = $this->user(id: 10, role: Role::WRITER);

        self::assertTrue($policy->canUpdate(
            user: $writer,
            article: new ArticlePolicyArticle(id: 1, status: 'draft', authorId: 10),
        ));
        self::assertFalse($policy->canUpdate(
            user: $writer,
            article: new ArticlePolicyArticle(id: 2, status: 'draft', authorId: 11),
        ));
        self::assertFalse($policy->canUpdate(
            user: $writer,
            article: new ArticlePolicyArticle(id: 3, status: 'draft', authorId: null),
        ));
    }

    public function testEditorAndAdminCanUpdateOtherUsersArticle(): void
    {
        $policy = $this->policy();
        $article = new ArticlePolicyArticle(id: 1, status: 'draft', authorId: 10);

        self::assertTrue($policy->canUpdate(user: $this->user(id: 20, role: Role::EDITOR), article: $article));
        self::assertTrue($policy->canUpdate(user: $this->user(id: 30, role: Role::ADMIN), article: $article));
    }

    public function testViewerCannotUpdateArticle(): void
    {
        self::assertFalse($this->policy()->canUpdate(
            user: $this->user(id: 10, role: Role::VIEWER),
            article: new ArticlePolicyArticle(id: 1, status: 'draft', authorId: 10),
        ));
    }

    public function testPublishDeleteAndRestoreFollowPermissions(): void
    {
        $policy = $this->policy();

        self::assertFalse($policy->canPublish(user: $this->user(role: Role::WRITER)));
        self::assertTrue($policy->canPublish(user: $this->user(role: Role::EDITOR)));
        self::assertFalse($policy->canDelete(user: $this->user(role: Role::WRITER)));
        self::assertTrue($policy->canDelete(user: $this->user(role: Role::EDITOR)));
        self::assertTrue($policy->canRestore(user: $this->user(role: Role::EDITOR)));
    }

    private function policy(): ArticlePolicy
    {
        return new ArticlePolicy(
            authorization: new AuthorizationService(rolePermissions: new RolePermissions()),
            roles: new RoleHierarchy(),
        );
    }

    private function user(int $id = 1, string $role = Role::ADMIN): CurrentCmsUser
    {
        return new CurrentCmsUser(id: $id, email: 'user@example.test', name: 'CMS User', role: $role);
    }
}
