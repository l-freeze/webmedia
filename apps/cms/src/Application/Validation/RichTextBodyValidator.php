<?php

declare(strict_types=1);

namespace App\Cms\Application\Validation;

use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class RichTextBodyValidator
{
    public static function validate(string $bodyFormat, string $body): ValidationOutput
    {
        if ($bodyFormat !== 'rich_text') {
            return ValidationOutput::valid();
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || ($decoded['type'] ?? '') !== 'doc') {
            return ValidationOutput::invalid(new ValidationError(field: 'body', message: 'リッチエディター本文の形式が正しくありません。'));
        }
        /** @var array<string, mixed> $decoded */

        if (self::isEmptyDocument($decoded)) {
            return ValidationOutput::invalid(new ValidationError(field: 'body', message: '本文を入力してください。'));
        }

        return ValidationOutput::valid();
    }

    /** @param array<string, mixed> $node */
    private static function isEmptyDocument(array $node): bool
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
                if (!self::isEmptyDocument($child)) {
                    return false;
                }
            }
        }

        return true;
    }
}
