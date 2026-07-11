<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\Update;

final readonly class Input
{
    private function __construct(
        public int $id,
        public string $status,
        public string $adminNote,
    ) {
    }

    public static function create(int $id, string $status, string $adminNote): self
    {
        return new self(id: $id, status: trim($status), adminNote: $adminNote);
    }
}
