<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\Tag;

interface ITagRepository
{
    public function create(Tag $tag): void;

    public function update(int $id, Tag $tag): bool;

    public function delete(int $id): bool;

    public function slugExists(string $slug, ?int $exceptId): bool;
}
