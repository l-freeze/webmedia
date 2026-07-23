<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\ArticleBody;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleCreatePermission;
use App\Cms\Domain\Value\ArticleDeletePermission;
use App\Cms\Domain\Value\ArticleExcerpt;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticlePublishPermission;
use App\Cms\Domain\Value\ArticleRestorePermission;
use App\Cms\Domain\Value\ArticleSlug;
use App\Cms\Domain\Value\ArticleStatus;
use App\Cms\Domain\Value\ArticleTitle;
use App\Cms\Domain\Value\ArticleUpdatePermission;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\CategoryId;
use App\Cms\Domain\Value\EyecatchPath;
use App\Cms\Domain\Value\MetaDescription;
use App\Cms\Domain\Value\PublishedEndsAt;
use App\Cms\Domain\Value\PublishedAt;
use App\Cms\Domain\Value\SeoTitle;

final readonly class Article
{
    public function __construct(
        public ?ArticleId $id,
        public ArticleTitle $title,
        public ArticleSlug $slug,
        public ArticleBody $body,
        public ArticleBodyFormat $bodyFormat,
        public ArticleExcerpt $excerpt,
        public ArticleStatus $status,
        public ?CategoryId $categoryId,
        public ?EyecatchPath $eyecatchPath,
        public ?PublishedAt $publishedAt,
        public ?PublishedEndsAt $publishedEndsAt,
        public SeoTitle $seoTitle,
        public MetaDescription $metaDescription,
        public ?AdminUserId $authorId,
    ) {
    }

    public static function create(
        ArticleCreatePermission $permission,
        ArticleTitle $title,
        ArticleSlug $slug,
        ArticleBody $body,
        ArticleBodyFormat $bodyFormat,
        ArticleExcerpt $excerpt,
        ArticleStatus $status,
        ?CategoryId $categoryId,
        ?EyecatchPath $eyecatchPath,
        ?PublishedAt $publishedAt,
        ?PublishedEndsAt $publishedEndsAt,
        SeoTitle $seoTitle,
        MetaDescription $metaDescription,
        ?AdminUserId $authorId,
        ?ArticlePublishPermission $publishPermission,
    ): self {
        $article = new self(
            id: null,
            title: $title,
            slug: $slug,
            body: $body,
            bodyFormat: $bodyFormat,
            excerpt: $excerpt,
            status: $status,
            categoryId: $categoryId,
            eyecatchPath: $eyecatchPath,
            publishedAt: $publishedAt,
            publishedEndsAt: $publishedEndsAt,
            seoTitle: $seoTitle,
            metaDescription: $metaDescription,
            authorId: $authorId,
        );
        self::assertPublishPermission(article: $article, permission: $publishPermission);

        return $article;
    }

    public function update(
        ArticleUpdatePermission $permission,
        ArticleTitle $title,
        ArticleSlug $slug,
        ArticleBody $body,
        ArticleBodyFormat $bodyFormat,
        ArticleExcerpt $excerpt,
        ArticleStatus $status,
        ?CategoryId $categoryId,
        ?EyecatchPath $eyecatchPath,
        ?PublishedAt $publishedAt,
        ?PublishedEndsAt $publishedEndsAt,
        SeoTitle $seoTitle,
        MetaDescription $metaDescription,
        ?ArticlePublishPermission $publishPermission,
    ): self {
        $article = new self(
            id: $this->id,
            title: $title,
            slug: $slug,
            body: $body,
            bodyFormat: $bodyFormat,
            excerpt: $excerpt,
            status: $status,
            categoryId: $categoryId,
            eyecatchPath: $eyecatchPath,
            publishedAt: $publishedAt,
            publishedEndsAt: $publishedEndsAt,
            seoTitle: $seoTitle,
            metaDescription: $metaDescription,
            authorId: $this->authorId,
        );
        self::assertPublishPermission(article: $article, permission: $publishPermission);

        return $article;
    }

    public function trash(ArticleDeletePermission $permission): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            slug: $this->slug,
            body: $this->body,
            bodyFormat: $this->bodyFormat,
            excerpt: $this->excerpt,
            status: ArticleStatus::Trashed,
            categoryId: $this->categoryId,
            eyecatchPath: $this->eyecatchPath,
            publishedAt: $this->publishedAt,
            publishedEndsAt: $this->publishedEndsAt,
            seoTitle: $this->seoTitle,
            metaDescription: $this->metaDescription,
            authorId: $this->authorId,
        );
    }

    public function restore(ArticleRestorePermission $permission, Article $version): self
    {
        return new self(
            id: $this->id,
            title: $version->title,
            slug: $version->slug,
            body: $version->body,
            bodyFormat: $version->bodyFormat,
            excerpt: $version->excerpt,
            status: $version->status,
            categoryId: $version->categoryId,
            eyecatchPath: $version->eyecatchPath,
            publishedAt: $version->publishedAt,
            publishedEndsAt: $version->publishedEndsAt,
            seoTitle: $version->seoTitle,
            metaDescription: $version->metaDescription,
            authorId: $this->authorId,
        );
    }

    private static function assertPublishPermission(Article $article, ?ArticlePublishPermission $permission): void
    {
        if ($article->status === ArticleStatus::Published && $permission === null) {
            throw new \LogicException('Publishing an article requires ArticlePublishPermission.');
        }
    }
}
