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
        $rows = $this->pdo->query('SELECT status, COUNT(*) AS count FROM articles GROUP BY status')->fetchAll();
        $stats = ['published' => 0, 'draft' => 0, 'scheduled' => 0];
        foreach ($rows as $row) {
            if (array_key_exists((string) $row['status'], $stats)) {
                $stats[(string) $row['status']] = (int) $row['count'];
            }
        }
        $inquiries = (int) $this->pdo->query('SELECT COUNT(*) FROM inquiries WHERE status <> "closed"')->fetchColumn();
        return new Stats($stats['published'], $stats['draft'], $stats['scheduled'], $inquiries);
    }
}
