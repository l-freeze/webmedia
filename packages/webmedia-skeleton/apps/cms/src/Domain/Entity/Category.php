<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\CategoryDescription;
use App\Cms\Domain\Value\CategoryId;
use App\Cms\Domain\Value\CategoryName;
use App\Cms\Domain\Value\CategorySlug;

final readonly class Category
{
    public function __construct(
        public ?CategoryId $id,
        public CategoryName $name,
        public CategorySlug $slug,
        public CategoryDescription $description,
    ) {
    }
}
