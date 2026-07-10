<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

final readonly class Category
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $description,
    ) {
    }
}
