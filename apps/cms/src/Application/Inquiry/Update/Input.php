<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\Update;

final readonly class Input
{
    public function __construct(
        public int $id,
        public string $status,
        public string $adminNote,
    ) {
    }
}
