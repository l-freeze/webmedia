<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\History;

final readonly class Input
{
    public function __construct(public int $articleId)
    {
    }
}
