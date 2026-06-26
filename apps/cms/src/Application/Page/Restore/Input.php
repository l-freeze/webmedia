<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Restore;

final readonly class Input
{
    public function __construct(
        public int $pageId,
        public int $versionNo,
    ) {
    }
}
