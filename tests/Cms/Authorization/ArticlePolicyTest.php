<?php

declare(strict_types=1);

namespace Tests\Cms\Authorization;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Exception\ResourceOperationDeniedException;
use App\Cms\Domain\Policy\ArticlePolicy;
use App\Cms\Domain\Value\ArticleBody;
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
        $writer = $this->actor(id: 10, role: CmsRole::WRITER);

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

        self::assertSame(20, $policy->authorizeUpdate(actor: $this->actor(id: 20, role: CmsRole::EDITOR), article: $article)->actor->id);
        self::assertSame(30, $policy->authorizeUpdate(actor: $this->actor(id: 30, role: CmsRole::ADMIN), article: $article)->actor->id);
    }

    public function testViewerCannotUpdateArticle(): void
    {
        $this->expectException(ResourceOperationDeniedException::class);
        $this->policy()->authorizeUpdate(
            actor: $this->actor(id: 10, role: CmsRole::VIEWER),
            article: $this->article(authorId: 10),
        );
    }

    public function testPublishDeleteAndRestoreRequireEditorOrHigher(): void
    {
        $policy = $this->policy();

        self::assertSame(CmsRole::EDITOR, $policy->authorizePublish($this->actor(role: CmsRole::EDITOR))->actor->role);
        self::assertSame(CmsRole::EDITOR, $policy->authorizeDelete($this->actor(role: CmsRole::EDITOR))->actor->role);
        self::assertSame(CmsRole::EDITOR, $policy->authorizeRestore($this->actor(role: CmsRole::EDITOR))->actor->role);

        $this->expectException(ResourceOperationDeniedException::class);
        $policy->authorizePublish($this->actor(role: CmsRole::WRITER));
    }

    private function policy(): ArticlePolicy
    {
        return new ArticlePolicy();
    }

    private function actor(int $id = 1, string $role = CmsRole::ADMIN): CmsActor
    {
        return new CmsActor(id: $id, role: $role);
    }

    private function article(?int $authorId): Article
    {
        return new Article(
            id: new ArticleId(1),
            title: new ArticleTitle('Title'),
            slug: new ArticleSlug('slug'),
            body: new ArticleBody('Body'),
            bodyFormat: new ArticleBodyFormat(ArticleBodyFormat::MARKDOWN),
            excerpt: new ArticleExcerpt(''),
            status: new ArticleStatus('draft'),
            categoryId: null,
            eyecatchPath: null,
            publishedAt: null,
            publishedEndsAt: null,
            seoTitle: new SeoTitle(''),
            metaDescription: new MetaDescription(''),
            authorId: $authorId,
        );
    }
}
