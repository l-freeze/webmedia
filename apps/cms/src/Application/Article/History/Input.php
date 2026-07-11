<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\History;

final readonly class Input
{
    private function __construct(public int $articleId)
    {
    }

    public static function create(int $articleId): self
    {
        return new self(articleId: $articleId);
    }
}
