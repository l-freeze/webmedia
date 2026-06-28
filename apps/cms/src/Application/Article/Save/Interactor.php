<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Application\Article\Policy\ArticlePolicy;
use App\Cms\Application\Article\Policy\Query\IArticlePolicyQuery;
use App\Cms\Application\Auth\Authorization\Exception\AuthorizationException;
use App\Cms\Application\Auth\Authorization\Value\PermissionCode;
use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationInput;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(
        private IArticleRepository $articles,
        private IArticlePolicyQuery $articlePolicyQuery,
        private ArticlePolicy $articlePolicy,
    ) {
    }

    public function execute(Input $input): Output
    {
        $values = new ValidationInput(values: $input->data);
        $validation = (new FormValidator(input: $values))
            ->required(field: 'title', label: 'タイトル')
            ->required(field: 'slug', label: 'スラッグ')
            ->required(field: 'body_format', label: '入力種別')
            ->required(field: 'body', label: '本文')
            ->output();
        $validation = SlugValidator::validate(input: $values, validation: $validation);

        if (!$validation->isValid()) {
            return Output::invalid(validation: $validation);
        }

        $richTextValidation = $this->validateRichTextBody(values: $values);
        if (!$richTextValidation->isValid()) {
            return Output::invalid(validation: $richTextValidation);
        }

        if ($this->articles->slugExists(slug: $values->string(field: 'slug'), exceptId: $input->id)) {
            return Output::invalid(validation: ValidationOutput::invalid(
                new ValidationError(field: 'slug', message: 'このスラッグは既に使われています。'),
            ));
        }

        $status = $values->string(field: 'status');
        $this->authorize(input: $input, status: $status === '' ? 'draft' : $status);

        $articleData = $input->data;
        if ($input->id === null) {
            $articleData['author_id'] = $input->currentUser->id;
        }

        return Output::saved(articleId: $this->articles->save(article: Article::fromInput(id: $input->id, data: $articleData)));
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

        if ($this->isEmptyRichTextDocument(node: $decoded)) {
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
                if (!$this->isEmptyRichTextDocument(node: $child)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function authorize(Input $input, string $status): void
    {
        if ($input->id === null) {
            if (!$this->articlePolicy->canCreate(user: $input->currentUser)) {
                throw new AuthorizationException(userId: $input->currentUser->id, permission: PermissionCode::ARTICLE_CREATE);
            }
        } else {
            $article = $this->articlePolicyQuery->findById(id: $input->id);
            if ($article === null || !$this->articlePolicy->canUpdate(user: $input->currentUser, article: $article)) {
                throw new AuthorizationException(userId: $input->currentUser->id, permission: PermissionCode::ARTICLE_UPDATE);
            }
        }

        if ($status === 'published' && !$this->articlePolicy->canPublish(user: $input->currentUser)) {
            throw new AuthorizationException(userId: $input->currentUser->id, permission: PermissionCode::ARTICLE_PUBLISH);
        }
    }
}
