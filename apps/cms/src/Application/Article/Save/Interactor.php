<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Application\Article\PublicationDateTime;
use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Exception\ResourceOperationDeniedException;
use App\Cms\Domain\Policy\ArticlePolicy;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticlePublishPermission;
use App\Cms\Domain\Value\ArticleBody;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleExcerpt;
use App\Cms\Domain\Value\ArticleSlug;
use App\Cms\Domain\Value\ArticleStatus;
use App\Cms\Domain\Value\ArticleTitle;
use App\Cms\Domain\Value\CategoryId;
use App\Cms\Domain\Value\CmsActor;
use App\Cms\Domain\Value\CmsRole;
use App\Cms\Domain\Value\EyecatchPath;
use App\Cms\Domain\Value\MetaDescription;
use App\Cms\Domain\Value\PublishedAt;
use App\Cms\Domain\Value\PublishedEndsAt;
use App\Cms\Domain\Value\SeoTitle;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(
        private IArticleRepository $articles,
        private ArticlePolicy $articlePolicy,
        private PublicationDateTime $publicationDateTime,
    ) {
    }

    public function execute(Input $input): Output
    {
        $validation = $this->validate($input);
        if (!$validation->isValid()) {
            return Output::invalid($validation);
        }

        $validation = $this->validateSlugUniqueness($input);
        if (!$validation->isValid()) {
            return Output::invalid($validation);
        }

        $article = $this->createEntity($input);
        $articleId = $this->articles->save($article);

        return Output::saved($articleId);
    }

    private function validate(Input $input): ValidationOutput
    {
        return InputValidator::validate($input);
    }

    private function validateSlugUniqueness(Input $input): ValidationOutput
    {
        $slugExists = $this->articles->slugExists(slug: $input->slug, exceptId: $input->id);
        if ($slugExists) {
            return ValidationOutput::invalid(
                new ValidationError(field: 'slug', message: 'このスラッグは既に使われています。'),
            );
        }

        return ValidationOutput::valid();
    }

    private function createEntity(Input $input): Article
    {
        $actor = new CmsActor(
            id: new AdminUserId(value: $input->currentUser->id),
            role: CmsRole::from($input->currentUser->role),
        );
        $status = ArticleStatus::from($input->status);
        $publishPermission = $this->publishPermissionIfNeeded(actor: $actor, status: $status);

        if ($input->id === null) {
            return Article::create(
                permission: $this->articlePolicy->authorizeCreate($actor),
                title: new ArticleTitle(value: $input->title),
                slug: new ArticleSlug(value: $input->slug),
                body: new ArticleBody(value: $input->body),
                bodyFormat: ArticleBodyFormat::from($input->bodyFormat),
                excerpt: new ArticleExcerpt(value: $input->excerpt),
                status: $status,
                categoryId: $this->categoryId($input),
                eyecatchPath: $this->eyecatchPath($input),
                publishedAt: $this->publishedAt($input),
                publishedEndsAt: $this->publishedEndsAt($input),
                seoTitle: new SeoTitle(value: $input->seoTitle),
                metaDescription: new MetaDescription(value: $input->metaDescription),
                authorId: new AdminUserId(value: $input->currentUser->id),
                publishPermission: $publishPermission,
            );
        }

        $article = $this->articles->findById(new ArticleId($input->id));
        if ($article === null) {
            throw new ResourceOperationDeniedException(actorId: $input->currentUser->id, operation: 'article.update');
        }

        return $article->update(
            permission: $this->articlePolicy->authorizeUpdate(actor: $actor, article: $article),
            title: new ArticleTitle(value: $input->title),
            slug: new ArticleSlug(value: $input->slug),
            body: new ArticleBody(value: $input->body),
            bodyFormat: ArticleBodyFormat::from($input->bodyFormat),
            excerpt: new ArticleExcerpt(value: $input->excerpt),
            status: $status,
            categoryId: $this->categoryId($input),
            eyecatchPath: $this->eyecatchPath($input),
            publishedAt: $this->publishedAt($input),
            publishedEndsAt: $this->publishedEndsAt($input),
            seoTitle: new SeoTitle(value: $input->seoTitle),
            metaDescription: new MetaDescription(value: $input->metaDescription),
            publishPermission: $publishPermission,
        );
    }

    private function publishPermissionIfNeeded(CmsActor $actor, ArticleStatus $status): ?ArticlePublishPermission
    {
        if ($status !== ArticleStatus::Published) {
            return null;
        }

        $publishPermission = $this->articlePolicy->authorizePublish($actor);

        return $publishPermission;
    }

    private function categoryId(Input $input): ?CategoryId
    {
        return $input->categoryId === '' ? null : new CategoryId(value: (int) $input->categoryId);
    }

    private function eyecatchPath(Input $input): ?EyecatchPath
    {
        return $input->eyecatchPath === '' ? null : new EyecatchPath(value: $input->eyecatchPath);
    }

    private function publishedAt(Input $input): ?PublishedAt
    {
        return $input->publishedAt === '' ? null : new PublishedAt(value: $this->publicationDateTime->toStorage($input->publishedAt));
    }

    private function publishedEndsAt(Input $input): ?PublishedEndsAt
    {
        return $input->publishedEndsAt === '' ? null : new PublishedEndsAt(value: $this->publicationDateTime->toStorage($input->publishedEndsAt));
    }
}
