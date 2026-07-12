<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

enum ArticleBodyFormat: string
{
    case Markdown = 'markdown';
    case Html = 'html';
    case RichText = 'rich_text';
}
