<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class ArticleUpdatePermission
{
    public function __construct(public CmsActor $actor)
    {
    }
}
