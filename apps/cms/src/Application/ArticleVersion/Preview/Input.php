<?php

declare(strict_types=1);

namespace App\Cms\Application\ArticleVersion\Preview;

final readonly class Input
{
    private function __construct(public int $articleId, public int $versionNo)
    {
    }

    public static function create(int $articleId, int $versionNo): self
    {
        return new self(articleId: $articleId, versionNo: $versionNo);
    }
}
