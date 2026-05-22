<?php

declare(strict_types=1);

namespace App\Front\Article\Domain;

interface ArticleQueryService
{
    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit = 10): array;

    /** @return array<string, mixed>|null */
    public function findPublishedBySlug(string $slug): ?array;

    /** @return array<int, array<string, mixed>> */
    public function search(string $keyword): array;

    /** @return array<int, array<string, mixed>> */
    public function related(int $articleId, ?int $categoryId): array;
}
