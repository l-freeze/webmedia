<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

final readonly class Tag
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {
    }
}
