<?php

declare(strict_types=1);

namespace App\Cms\Media\Infrastructure\Persistence;

use App\Cms\Media\Query\ListMedia\MediaQueryService;
use PDO;

final readonly class PdoMediaQueryService implements MediaQueryService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(): array
    {
        return $this->pdo->query('SELECT file_name, mime_type, file_size, created_at FROM media_files ORDER BY created_at DESC')->fetchAll();
    }
}
