<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class ArticleBodyFormat
{
    public const MARKDOWN = 'markdown';
    public const HTML = 'html';

    public string $value;

    public function __construct(string $value)
    {
        $this->value = in_array($value, [self::MARKDOWN, self::HTML], true) ? $value : self::MARKDOWN;
    }
}
