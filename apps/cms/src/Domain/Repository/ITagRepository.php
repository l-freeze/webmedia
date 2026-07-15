<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\Tag;
use App\Cms\Domain\Value\TagId;
use App\Cms\Domain\Value\TagSlug;

interface ITagRepository
{
    public function save(Tag $tag): ?TagId;

    public function delete(TagId $id): bool;

    public function slugExists(TagSlug $slug, ?TagId $exceptId): bool;
}
