<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\History;

use App\Cms\Application\Article\History\Value\ArticleVersion;

final readonly class Output
{
    /** @param array<int, ArticleVersion> $versions */
    public function __construct(public int $articleId, public array $versions)
    {
    }
}
