<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Exception\ResourceOperationDeniedException;
use App\Cms\Domain\Policy\ArticlePolicy;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticlePublishPermission;
use App\Cms\Domain\Value\CmsActor;
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

        $articleData = $input->data;
        if ($input->id === null) {
            $articleData['author_id'] = $input->currentUser->id;
        }

        return Output::saved($this->articles->save($this->articleFromInput(input: $input, data: $articleData)));
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

    /** @param array<string, mixed> $data */
    private function articleFromInput(Input $input, array $data): Article
    {
        $actor = new CmsActor(id: $input->currentUser->id, role: $input->currentUser->role);
        $publishPermission = $this->publishPermissionIfNeeded(actor: $actor, data: $data);

        if ($input->id === null) {
            return Article::create(
                permission: $this->articlePolicy->authorizeCreate($actor),
                data: $data,
                publishPermission: $publishPermission,
            );
        }

        $article = $this->articles->findById(new ArticleId($input->id));
        if ($article === null) {
            throw new ResourceOperationDeniedException(actorId: $input->currentUser->id, operation: 'article.update');
        }

        return $article->update(
            permission: $this->articlePolicy->authorizeUpdate(actor: $actor, article: $article),
            data: $data,
            publishPermission: $publishPermission,
        );
    }

    /** @param array<string, mixed> $data */
    private function publishPermissionIfNeeded(CmsActor $actor, array $data): ?ArticlePublishPermission
    {
        $status = $data['status'] ?? 'draft';
        if (!is_scalar($status) || (string) $status !== 'published') {
            return null;
        }

        return $this->articlePolicy->authorizePublish($actor);
    }
}
