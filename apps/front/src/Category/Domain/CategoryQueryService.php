<?php

declare(strict_types=1);

namespace App\Front\Category\Domain;

interface CategoryQueryService
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array;

    /** @return array{name:string,slug:string,description:?string}|null */
    public function find(string $slug): ?array;

    /** @return array<int, array<string, mixed>> */
    public function articles(string $slug): array;
}
