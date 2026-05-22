<?php

declare(strict_types=1);

namespace App\Front\Page\Domain;

interface PageQueryService
{
    /** @return array<string, mixed>|null */
    public function findPublishedBySlug(string $slug): ?array;
}
