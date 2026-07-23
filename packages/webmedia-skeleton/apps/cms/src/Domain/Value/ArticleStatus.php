<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Private = 'private';
    case Trashed = 'trashed';
}
