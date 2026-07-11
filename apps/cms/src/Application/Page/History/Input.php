<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\History;

final readonly class Input
{
    private function __construct(public int $pageId)
    {
    }

    public static function create(int $pageId): self
    {
        return new self(pageId: $pageId);
    }
}
