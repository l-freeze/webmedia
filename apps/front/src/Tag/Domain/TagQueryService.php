<?php

declare(strict_types=1);

namespace App\Front\Tag\Domain;

interface TagQueryService
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array;

    /** @return array{name:string,slug:string}|null */
    public function find(string $slug): ?array;

    /** @return array<int, array<string, mixed>> */
    public function articles(string $slug): array;
}
