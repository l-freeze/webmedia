<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Save;

use App\Cms\Application\Validation\RichTextBodyValidator;
use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Value\PageBodyFormat;
use App\Cms\Domain\Value\PageStatus;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class InputValidator
{
    public static function validate(Input $input): ValidationOutput
    {
        $candidates = [
            self::required(field: 'title', label: 'タイトル', value: $input->title),
            self::required(field: 'slug', label: 'スラッグ', value: $input->slug),
            self::required(field: 'body_format', label: '入力種別', value: $input->bodyFormat),
            self::enumValue(field: 'body_format', label: '入力種別', value: $input->bodyFormat, enum: PageBodyFormat::class),
            self::required(field: 'body', label: '本文', value: $input->body),
            self::required(field: 'status', label: '状態', value: $input->status),
            self::enumValue(field: 'status', label: '状態', value: $input->status, enum: PageStatus::class),
        ];
        $errors = [];
        foreach ($candidates as $error) {
            if ($error !== null) {
                $errors[] = $error;
            }
        }
        $validation = ValidationOutput::invalid(...$errors);
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

    /** @param class-string<\BackedEnum> $enum */
    private static function enumValue(string $field, string $label, string $value, string $enum): ?ValidationError
    {
        return $value !== '' && $enum::tryFrom($value) === null
            ? new ValidationError(field: $field, message: "{$label}を正しく選択してください。")
            : null;
    }
}
