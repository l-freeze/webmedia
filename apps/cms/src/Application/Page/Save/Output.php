<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Save;

use App\Cms\Domain\Value\PageId;

final readonly class Output
{
    public function __construct(public PageId $pageId)
    {
    }
}
