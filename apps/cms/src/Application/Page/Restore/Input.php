<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Restore;

final readonly class Input
{
    private function __construct(
        public int $pageId,
        public int $versionNo,
    ) {
    }

    public static function create(int $pageId, int $versionNo): self
    {
        return new self(pageId: $pageId, versionNo: $versionNo);
    }
}
