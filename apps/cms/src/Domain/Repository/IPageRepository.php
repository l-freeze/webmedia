<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Value\PageId;

interface IPageRepository
{
    public function save(Page $page): PageId;

    public function slugExists(string $slug, ?int $exceptId): bool;
}
