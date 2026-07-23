<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Shared\Time\Application\IClock;

final readonly class ArticlePublicationCondition
{
    public function __construct(private IClock $clock)
    {
    }

    public function sql(string $articleAlias = 'a'): string
    {
        return "{$articleAlias}.status = 'published'"
            . " AND {$articleAlias}.published_at IS NOT NULL"
            . " AND {$articleAlias}.published_at <= :published_now"
            . " AND ({$articleAlias}.published_ends_at IS NULL OR {$articleAlias}.published_ends_at > :ends_now)";
    }

    /** @return array{published_now: string, ends_now: string} */
    public function parameters(): array
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        return ['published_now' => $now, 'ends_now' => $now];
    }
}
