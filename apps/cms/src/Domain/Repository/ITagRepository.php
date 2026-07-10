<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

interface ITagRepository
{
    /** @param array{name:mixed,slug:mixed} $data */
    public function create(array $data): void;

    /** @param array{name:mixed,slug:mixed} $data */
    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function slugExists(string $slug, ?int $exceptId): bool;
}
