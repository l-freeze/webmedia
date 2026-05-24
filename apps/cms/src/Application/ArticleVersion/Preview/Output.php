<?php

declare(strict_types=1);

namespace App\Cms\Application\ArticleVersion\Preview;

use App\Cms\Application\ArticleVersion\Preview\Value\ArticleVersion;

final readonly class Output
{
    public function __construct(public ?ArticleVersion $articleVersion)
    {
    }
}
