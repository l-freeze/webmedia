<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Save;

use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Value\PageBodyFormat;
use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationInput;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(private IPageRepository $pages)
    {
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

        if ($this->pages->slugExists(slug: $values->string(field: 'slug'), exceptId: $input->id)) {
            return Output::invalid(validation: ValidationOutput::invalid(
                new ValidationError(field: 'slug', message: 'このスラッグは既に使われています。'),
            ));
        }

        return Output::saved(pageId: $this->pages->save(page: Page::fromInput(id: $input->id, data: $input->data)));
    }

    private function validateRichTextBody(ValidationInput $values): ValidationOutput
    {
        if ($values->string(field: 'body_format') !== PageBodyFormat::RICH_TEXT) {
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
}
