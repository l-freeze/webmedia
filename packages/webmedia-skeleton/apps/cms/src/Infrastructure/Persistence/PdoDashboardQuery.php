<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Dashboard\Show\Query\IDashboardQuery;
use App\Cms\Application\Dashboard\Show\Value\Stats;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use DateTimeImmutable;
use PDO;

final readonly class PdoDashboardQuery implements IDashboardQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function stats(DateTimeImmutable $now): Stats
    {
        $currentTime = $now->format('Y-m-d H:i:s');
        $published = $this->count(
            sql: "SELECT COUNT(*) FROM articles WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= :published_now AND (published_ends_at IS NULL OR published_ends_at > :ends_now)",
            params: ['published_now' => $currentTime, 'ends_now' => $currentTime],
        );
        $draft = $this->count(sql: "SELECT COUNT(*) FROM articles WHERE status = 'draft'");
        $scheduled = $this->count(
            sql: "SELECT COUNT(*) FROM articles WHERE status = 'published' AND published_at IS NOT NULL AND published_at > :current_time",
            params: ['current_time' => $currentTime],
        );
        $inquiries = $this->count(sql: "SELECT COUNT(*) FROM inquiries WHERE status <> 'closed'");
        return new Stats(published: $published, draft: $draft, scheduled: $scheduled, inquiries: $inquiries);
    }

    /** @param array<string, scalar|null> $params */
    private function count(string $sql, array $params = []): int
    {
        $statement = PdoHelper::prepare(pdo: $this->pdo, sql: $sql);
        PdoHelper::execute(statement: $statement, params: $params);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $statement)])->int(key: 'count');
    }
}
