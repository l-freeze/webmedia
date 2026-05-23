<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Media\List\Query\IMediaQuery;
use App\Cms\Application\Media\List\Value\Media;
use PDO;

final readonly class PdoMediaQuery implements IMediaQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Media> */
    public function list(): array
    {
        return array_map(fn (array $row): Media => new Media(
            fileName: (string) $row['file_name'],
            mimeType: (string) $row['mime_type'],
            fileSize: (int) $row['file_size'],
            createdAt: (string) $row['created_at'],
        ), $this->pdo->query('SELECT file_name, mime_type, file_size, created_at FROM media_files ORDER BY created_at DESC')->fetchAll());
    }
}
