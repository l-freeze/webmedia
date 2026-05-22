<?php

declare(strict_types=1);

namespace App\Cms\Trash\Infrastructure\Persistence;

use App\Cms\Trash\Query\ListTrash\TrashQueryService;
use PDO;

final readonly class PdoTrashQueryService implements TrashQueryService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(): array
    {
        return $this->pdo->query('SELECT title, slug, updated_at FROM articles WHERE status = "trashed" UNION ALL SELECT title, slug, updated_at FROM pages WHERE status = "trashed" ORDER BY updated_at DESC')->fetchAll();
    }
}
