<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\Category;
use App\Cms\Domain\Value\CategoryId;
use App\Cms\Domain\Value\CategorySlug;

interface ICategoryRepository
{
    public function save(Category $category): ?CategoryId;

    public function delete(CategoryId $id): bool;

    public function slugExists(CategorySlug $slug, ?CategoryId $exceptId): bool;
}
