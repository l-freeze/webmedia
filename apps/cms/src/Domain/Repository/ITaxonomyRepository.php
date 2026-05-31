<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

interface ITaxonomyRepository
{
    /** @param array<string, mixed> $data */
    public function createCategory(array $data): void;

    /** @param array<string, mixed> $data */
    public function createTag(array $data): void;

    /** @param array<string, mixed> $data */
    public function updateCategory(int $id, array $data): bool;

    /** @param array<string, mixed> $data */
    public function updateTag(int $id, array $data): bool;

    public function deleteCategory(int $id): bool;

    public function deleteTag(int $id): bool;
}
