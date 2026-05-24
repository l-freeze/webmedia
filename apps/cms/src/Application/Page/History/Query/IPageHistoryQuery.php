<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\History\Query;

use App\Cms\Application\Page\History\Value\PageVersion;

interface IPageHistoryQuery
{
    /** @return array<int, PageVersion> */
    public function versions(int $pageId): array;
}
