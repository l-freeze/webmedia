<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Exception\ResourceOperationDeniedException;
use App\Cms\Domain\Policy\ArticlePolicy;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleBody;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleExcerpt;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticlePublishPermission;
use App\Cms\Domain\Value\ArticleSlug;
use App\Cms\Domain\Value\ArticleStatus;
use App\Cms\Domain\Value\ArticleTitle;
use App\Cms\Domain\Value\CategoryId;
use App\Cms\Domain\Value\CmsActor;
use App\Cms\Domain\Value\EyecatchPath;
use App\Cms\Domain\Value\MetaDescription;
use App\Cms\Domain\Value\PublishedAt;
use App\Cms\Domain\Value\PublishedEndsAt;
use App\Cms\Domain\Value\SeoTitle;
use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationInput;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(
        private IArticleRepository $articles,
        private ArticlePolicy $articlePolicy,
    ) {
    }

    public function execute(Input $input): Output
    {
        $values = new ValidationInput($input->data);
        $validation = (new FormValidator($values))
            ->required(field: 'title', label: 'タイトル')
            ->required(field: 'slug', label: 'スラッグ')
            ->required(field: 'body_format', label: '入力種別')
            ->required(field: 'body', label: '本文')
            ->output();
        $validation = SlugValidator::validate(input: $values, validation: $validation);

        if (!$validation->isValid()) {
            return Output::invalid($validation);
        }

        $richTextValidation = $this->validateRichTextBody($values);
        if (!$richTextValidation->isValid()) {
            return Output::invalid($richTextValidation);
        }

        if ($this->articles->slugExists(slug: $values->string(field: 'slug'), exceptId: $input->id)) {
            return Output::invalid(ValidationOutput::invalid(
                new ValidationError(field: 'slug', message: 'このスラッグは既に使われています。'),
            ));
        }

        return Output::saved($this->articles->save($this->articleFromInput(input: $input, values: $values)));
    }

    private function validateRichTextBody(ValidationInput $values): ValidationOutput
    {
        if ($values->string(field: 'body_format') !== ArticleBodyFormat::RICH_TEXT) {
            return ValidationOutput::valid();
        }

        $decoded = json_decode($values->string(field: 'body'), true);
        if (!is_array($decoded) || ($decoded['type'] ?? '') !== 'doc') {
            return ValidationOutput::invalid(new ValidationError(field: 'body', message: 'リッチエディター本文の形式が正しくありません。'));
        }
        /** @var array<string, mixed> $decoded */

        if ($this->isEmptyRichTextDocument($decoded)) {
            return ValidationOutput::invalid(new ValidationError(field: 'body', message: '本文を入力してください。'));
        }

        return ValidationOutput::valid();
    }

    /** @param array<string, mixed> $node */
    private function isEmptyRichTextDocument(array $node): bool
    {
        $text = is_string($node['text'] ?? null) ? trim($node['text']) : '';
        if ($text !== '') {
            return false;
        }

        $type = is_string($node['type'] ?? null) ? $node['type'] : '';
        if ($type === 'image') {
            return false;
        }

        $content = $node['content'] ?? [];
        if (!is_array($content)) {
            return true;
        }

        foreach ($content as $child) {
            if (is_array($child)) {
                /** @var array<string, mixed> $child */
                if (!$this->isEmptyRichTextDocument($child)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function articleFromInput(Input $input, ValidationInput $values): Article
    {
        $actor = new CmsActor(id: $input->currentUser->id, role: $input->currentUser->role);
        $status = new ArticleStatus(value: $values->string(field: 'status'));
        $publishPermission = $this->publishPermissionIfNeeded(actor: $actor, status: $status);

        if ($input->id === null) {
            return Article::create(
                permission: $this->articlePolicy->authorizeCreate($actor),
                title: new ArticleTitle(value: $values->string(field: 'title')),
                slug: new ArticleSlug(value: $values->string(field: 'slug')),
                body: new ArticleBody(value: $values->string(field: 'body')),
                bodyFormat: new ArticleBodyFormat(value: $values->string(field: 'body_format')),
                excerpt: new ArticleExcerpt(value: $values->string(field: 'excerpt')),
                status: $status,
                categoryId: $this->categoryId(values: $values),
                eyecatchPath: $this->eyecatchPath(values: $values),
                publishedAt: $this->publishedAt(values: $values),
                publishedEndsAt: $this->publishedEndsAt(values: $values),
                seoTitle: new SeoTitle(value: $values->string(field: 'seo_title')),
                metaDescription: new MetaDescription(value: $values->string(field: 'meta_description')),
                authorId: $input->currentUser->id,
                publishPermission: $publishPermission,
            );
        }

        $article = $this->articles->findById(new ArticleId($input->id));
        if ($article === null) {
            throw new ResourceOperationDeniedException(actorId: $input->currentUser->id, operation: 'article.update');
        }

        return $article->update(
            permission: $this->articlePolicy->authorizeUpdate(actor: $actor, article: $article),
            title: new ArticleTitle(value: $values->string(field: 'title')),
            slug: new ArticleSlug(value: $values->string(field: 'slug')),
            body: new ArticleBody(value: $values->string(field: 'body')),
            bodyFormat: new ArticleBodyFormat(value: $values->string(field: 'body_format')),
            excerpt: new ArticleExcerpt(value: $values->string(field: 'excerpt')),
            status: $status,
            categoryId: $this->categoryId(values: $values),
            eyecatchPath: $this->eyecatchPath(values: $values),
            publishedAt: $this->publishedAt(values: $values),
            publishedEndsAt: $this->publishedEndsAt(values: $values),
            seoTitle: new SeoTitle(value: $values->string(field: 'seo_title')),
            metaDescription: new MetaDescription(value: $values->string(field: 'meta_description')),
            publishPermission: $publishPermission,
        );
    }

    private function publishPermissionIfNeeded(CmsActor $actor, ArticleStatus $status): ?ArticlePublishPermission
    {
        if ($status->value !== 'published') {
            return null;
        }

        return $this->articlePolicy->authorizePublish($actor);
    }

    private function categoryId(ValidationInput $values): ?CategoryId
    {
        $categoryId = $values->string(field: 'category_id');
        return $categoryId === '' ? null : new CategoryId(value: (int) $categoryId);
    }

    private function eyecatchPath(ValidationInput $values): ?EyecatchPath
    {
        $eyecatchPath = $values->string(field: 'eyecatch_path');
        return $eyecatchPath === '' ? null : new EyecatchPath(value: $eyecatchPath);
    }

    private function publishedAt(ValidationInput $values): ?PublishedAt
    {
        $publishedAt = $values->string(field: 'published_at');
        return $publishedAt === '' ? null : new PublishedAt(value: $publishedAt);
    }

    private function publishedEndsAt(ValidationInput $values): ?PublishedEndsAt
    {
        $publishedEndsAt = $values->string(field: 'published_ends_at');
        return $publishedEndsAt === '' ? null : new PublishedEndsAt(value: $publishedEndsAt);
    }
}
