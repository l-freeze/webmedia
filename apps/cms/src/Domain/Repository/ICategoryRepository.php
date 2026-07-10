<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\Category;

interface ICategoryRepository
{
    public function create(Category $category): void;

    public function update(int $id, Category $category): bool;

    public function delete(int $id): bool;

    public function slugExists(string $slug, ?int $exceptId): bool;
}
