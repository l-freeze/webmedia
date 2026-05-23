<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Domain\Value\ArticleId;

final readonly class Output
{
    public function __construct(public ArticleId $articleId)
    {
    }
}
