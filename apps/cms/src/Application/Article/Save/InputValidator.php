<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Application\Article\PublicationDateTime;
use App\Cms\Application\Validation\RichTextBodyValidator;
use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleStatus;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class InputValidator
{
    public static function validate(Input $input): ValidationOutput
    {
        $validation = ValidationOutput::invalid(...array_values(array_filter([
            self::required(field: 'title', label: 'タイトル', value: $input->title),
            self::required(field: 'slug', label: 'スラッグ', value: $input->slug),
            self::required(field: 'body_format', label: '入力種別', value: $input->bodyFormat),
            self::enumValue(field: 'body_format', label: '入力種別', value: $input->bodyFormat, enum: ArticleBodyFormat::class),
            self::required(field: 'body', label: '本文', value: $input->body),
            self::required(field: 'status', label: '状態', value: $input->status),
            self::enumValue(field: 'status', label: '状態', value: $input->status, enum: ArticleStatus::class),
            self::publicationDateTime(field: 'published_at', label: '公開日時', value: $input->publishedAt),
            self::publicationDateTime(field: 'published_ends_at', label: '公開終了日時', value: $input->publishedEndsAt),
        ])));
        $validation = SlugValidator::validate(slug: $input->slug, validation: $validation);

        if (!$validation->isValid()) {
            return $validation;
        }

        $validation = RichTextBodyValidator::validate(bodyFormat: $input->bodyFormat, body: $input->body);

        return $validation;
    }

    private static function required(string $field, string $label, string $value): ?ValidationError
    {
        return $value === '' ? new ValidationError(field: $field, message: "{$label}を入力してください。") : null;
    }

    private static function publicationDateTime(string $field, string $label, string $value): ?ValidationError
    {
        return PublicationDateTime::isValidEditorValue($value)
            ? null
            : new ValidationError(field: $field, message: "{$label}はYYYY-MM-DD HH:MM:SS形式で入力してください。");
    }

    /** @param class-string<\BackedEnum> $enum */
    private static function enumValue(string $field, string $label, string $value, string $enum): ?ValidationError
    {
        return $value !== '' && $enum::tryFrom($value) === null
            ? new ValidationError(field: $field, message: "{$label}を正しく選択してください。")
            : null;
    }
}
