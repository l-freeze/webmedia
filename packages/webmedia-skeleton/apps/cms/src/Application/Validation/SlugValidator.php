<?php

declare(strict_types=1);

namespace App\Cms\Application\Validation;

use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class SlugValidator
{
    public static function validate(string $slug, ValidationOutput $validation): ValidationOutput
    {
        if (!$validation->isValid()) {
            return $validation;
        }

        if ($slug === '' || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1) {
            return $validation;
        }

        return ValidationOutput::invalid(
            new ValidationError(field: 'slug', message: 'スラッグは半角英小文字・数字・ハイフンで入力してください（例: my-article-2026）。'),
        );
    }
}
