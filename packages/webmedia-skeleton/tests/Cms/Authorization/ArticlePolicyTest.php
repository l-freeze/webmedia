<?php

declare(strict_types=1);

namespace Tests\Cms\Authorization;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Exception\ResourceOperationDeniedException;
use App\Cms\Domain\Policy\ArticlePolicy;
use App\Cms\Domain\Value\ArticleBody;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleExcerpt;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleSlug;
use App\Cms\Domain\Value\ArticleStatus;
use App\Cms\Domain\Value\ArticleTitle;
use App\Cms\Domain\Value\CmsActor;
use App\Cms\Domain\Value\CmsRole;
use App\Cms\Domain\Value\MetaDescription;
use App\Cms\Domain\Value\SeoTitle;
use PHPUnit\Framework\TestCase;

final class ArticlePolicyTest extends TestCase
{
    public function testWriterCanUpdateOwnArticleOnly(): void
    {
        $policy = $this->policy();
        $writer = $this->actor(id: 10, role: CmsRole::Writer);

        self::assertSame($writer, $policy->authorizeUpdate(
            actor: $writer,
            article: $this->article(authorId: 10),
        )->actor);

        $this->expectException(ResourceOperationDeniedException::class);
        $policy->authorizeUpdate(actor: $writer, article: $this->article(authorId: 11));
    }

    public function testEditorAndAdminCanUpdateOtherUsersArticle(): void
    {
        $policy = $this->policy();
        $article = $this->article(authorId: 10);

        self::assertSame(20, $policy->authorizeUpdate(actor: $this->actor(id: 20, role: CmsRole::Editor), article: $article)->actor->id->value);
        self::assertSame(30, $policy->authorizeUpdate(actor: $this->actor(id: 30, role: CmsRole::Admin), article: $article)->actor->id->value);
    }

    public function testViewerCannotUpdateArticle(): void
    {
        $this->expectException(ResourceOperationDeniedException::class);
        $this->policy()->authorizeUpdate(
            actor: $this->actor(id: 10, role: CmsRole::Viewer),
            article: $this->article(authorId: 10),
        );
    }

    public function testPublishDeleteAndRestoreRequireEditorOrHigher(): void
    {
        $policy = $this->policy();

        self::assertSame(CmsRole::Editor, $policy->authorizePublish($this->actor(role: CmsRole::Editor))->actor->role);
        self::assertSame(CmsRole::Editor, $policy->authorizeDelete($this->actor(role: CmsRole::Editor))->actor->role);
        self::assertSame(CmsRole::Editor, $policy->authorizeRestore($this->actor(role: CmsRole::Editor))->actor->role);

        $this->expectException(ResourceOperationDeniedException::class);
        $policy->authorizePublish($this->actor(role: CmsRole::Writer));
    }

    private function policy(): ArticlePolicy
    {
        return new ArticlePolicy();
    }

    private function actor(int $id = 1, CmsRole $role = CmsRole::Admin): CmsActor
    {
        return new CmsActor(id: new AdminUserId(value: $id), role: $role);
    }

    private function article(?int $authorId): Article
    {
        return new Article(
            id: new ArticleId(1),
            title: new ArticleTitle('Title'),
            slug: new ArticleSlug('slug'),
            body: new ArticleBody('Body'),
            bodyFormat: ArticleBodyFormat::Markdown,
            excerpt: new ArticleExcerpt(''),
            status: ArticleStatus::Draft,
            categoryId: null,
            eyecatchPath: null,
            publishedAt: null,
            publishedEndsAt: null,
            seoTitle: new SeoTitle(''),
            metaDescription: new MetaDescription(''),
            authorId: $authorId === null ? null : new AdminUserId(value: $authorId),
        );
    }
}
