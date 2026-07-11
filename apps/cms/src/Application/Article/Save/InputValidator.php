<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Application\Validation\RichTextBodyValidator;
use App\Cms\Application\Validation\SlugValidator;
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
            self::required(field: 'body', label: '本文', value: $input->body),
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
}
