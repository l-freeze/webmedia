<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

interface ITaxonomyRepository
{
    /** @param array<string, mixed> $data */
    public function createCategory(array $data): void;
}
