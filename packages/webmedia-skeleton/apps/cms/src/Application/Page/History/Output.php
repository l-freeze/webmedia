<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\History;

use App\Cms\Application\Page\History\Value\PageVersion;

final readonly class Output
{
    /** @param array<int, PageVersion> $versions */
    public function __construct(public int $pageId, public array $versions)
    {
    }
}
