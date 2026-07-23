<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class ArticleExcerpt
{
    public function __construct(public string $value)
    {
    }
}
