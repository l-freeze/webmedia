<?php

declare(strict_types=1);

namespace App\Cms\Taxonomy\Domain;

interface TaxonomyRepository
{
    /** @return array<int, array<string, mixed>> */
    public function categories(): array;

    /** @return array<int, array<string, mixed>> */
    public function tags(): array;

    /** @param array<string, mixed> $data */
    public function createCategory(array $data): void;
}
