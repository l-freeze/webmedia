<?php

declare(strict_types=1);

namespace App\Cms\Article\Domain;

interface ArticleRepository
{
    /** @return array<int, array<string, mixed>> */
    public function list(string $keyword = ''): array;

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array;

    /** @return array<int, array<string, mixed>> */
    public function categories(): array;

    /** @param array<string, mixed> $data */
    public function save(?int $id, array $data): int;

    /** @return array<int, array<string, mixed>> */
    public function versions(int $articleId): array;

    public function restore(int $articleId, int $versionNo): void;

    /** @return array<string, int> */
    public function stats(): array;
}
