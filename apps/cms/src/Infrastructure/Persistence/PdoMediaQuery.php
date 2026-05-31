<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Edit\Query\IMediaQuery as EditMediaQuery;
use App\Cms\Application\Article\Edit\Value\Media as EditMedia;
use App\Cms\Application\Media\List\Query\IMediaQuery;
use App\Cms\Application\Media\List\Value\Media;
use App\Framework\Database\PdoHelper;
use PDO;

final readonly class PdoMediaQuery implements IMediaQuery, EditMediaQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Media> */
    public function list(): array
    {
        return array_map(fn (array $row): Media => new Media(
            id: (int) $row['id'],
            fileName: (string) $row['file_name'],
            path: (string) $row['path'],
            mimeType: (string) $row['mime_type'],
            fileSize: (int) $row['file_size'],
            altText: (string) ($row['alt_text'] ?? ''),
            caption: (string) ($row['caption'] ?? ''),
            createdAt: (string) $row['created_at'],
        ), PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, file_name, path, mime_type, file_size, alt_text, caption, created_at FROM media_files ORDER BY created_at DESC')));
    }

    /** @return array<int, EditMedia> */
    public function media(): array
    {
        return array_map(fn (array $row): EditMedia => new EditMedia(
            id: (int) $row['id'],
            fileName: (string) $row['file_name'],
            path: (string) $row['path'],
            altText: (string) ($row['alt_text'] ?? ''),
        ), PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT id, file_name, path, alt_text FROM media_files WHERE mime_type LIKE 'image/%' ORDER BY created_at DESC")));
    }
}
