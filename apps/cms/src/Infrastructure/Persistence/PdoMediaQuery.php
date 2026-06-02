<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Edit\Query\IMediaQuery as EditMediaQuery;
use App\Cms\Application\Article\Edit\Value\Media as EditMedia;
use App\Cms\Application\Media\List\Query\IMediaQuery;
use App\Cms\Application\Media\List\Value\Media;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoMediaQuery implements IMediaQuery, EditMediaQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Media> */
    public function list(): array
    {
        return array_map(static function (array $row): Media {
            $pdoRow = PdoRow::from(values: $row);

            return new Media(
                id: $pdoRow->int(key: 'id'),
                fileName: $pdoRow->string(key: 'file_name'),
                path: $pdoRow->string(key: 'path'),
                mimeType: $pdoRow->string(key: 'mime_type'),
                fileSize: $pdoRow->int(key: 'file_size'),
                altText: $pdoRow->string(key: 'alt_text'),
                caption: $pdoRow->string(key: 'caption'),
                createdAt: $pdoRow->string(key: 'created_at'),
            );
        }, PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, file_name, path, mime_type, file_size, alt_text, caption, created_at FROM media_files ORDER BY created_at DESC')));
    }

    /** @return array<int, EditMedia> */
    public function media(): array
    {
        return array_map(static function (array $row): EditMedia {
            $pdoRow = PdoRow::from(values: $row);

            return new EditMedia(
                id: $pdoRow->int(key: 'id'),
                fileName: $pdoRow->string(key: 'file_name'),
                path: $pdoRow->string(key: 'path'),
                altText: $pdoRow->string(key: 'alt_text'),
            );
        }, PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT id, file_name, path, alt_text FROM media_files WHERE mime_type LIKE 'image/%' ORDER BY created_at DESC")));
    }
}
