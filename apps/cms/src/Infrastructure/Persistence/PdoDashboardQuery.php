<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Dashboard\Show\Query\IDashboardQuery;
use App\Cms\Application\Dashboard\Show\Value\Stats;
use PDO;

final readonly class PdoDashboardQuery implements IDashboardQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function stats(): Stats
    {
        $published = (int) $this->pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW() AND (published_ends_at IS NULL OR published_ends_at > NOW())")->fetchColumn();
        $draft = (int) $this->pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'draft'")->fetchColumn();
        $scheduled = (int) $this->pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND published_at IS NOT NULL AND published_at > NOW()")->fetchColumn();
        $inquiries = (int) $this->pdo->query("SELECT COUNT(*) FROM inquiries WHERE status <> 'closed'")->fetchColumn();
        return new Stats($published, $draft, $scheduled, $inquiries);
    }
}
