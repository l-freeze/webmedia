<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Dashboard\Show\Query\IDashboardQuery;
use App\Cms\Application\Dashboard\Show\Value\Stats;
use App\Framework\Database\PdoHelper;
use PDO;

final readonly class PdoDashboardQuery implements IDashboardQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function stats(): Stats
    {
        $published = (int) PdoHelper::fetchColumn(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT COUNT(*) FROM articles WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW() AND (published_ends_at IS NULL OR published_ends_at > NOW())"));
        $draft = (int) PdoHelper::fetchColumn(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT COUNT(*) FROM articles WHERE status = 'draft'"));
        $scheduled = (int) PdoHelper::fetchColumn(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT COUNT(*) FROM articles WHERE status = 'published' AND published_at IS NOT NULL AND published_at > NOW()"));
        $inquiries = (int) PdoHelper::fetchColumn(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT COUNT(*) FROM inquiries WHERE status <> 'closed'"));
        return new Stats(published: $published, draft: $draft, scheduled: $scheduled, inquiries: $inquiries);
    }
}
