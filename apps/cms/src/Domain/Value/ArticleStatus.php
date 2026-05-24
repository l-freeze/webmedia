<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class ArticleStatus
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = in_array($value, ['draft', 'published', 'private', 'trashed'], true) ? $value : 'draft';
    }
}
