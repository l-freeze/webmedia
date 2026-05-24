<?php

declare(strict_types=1);

namespace App\Cms\Application\ArticleVersion\Preview;

final readonly class Input
{
    public function __construct(public int $articleId, public int $versionNo)
    {
    }
}
