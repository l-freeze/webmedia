<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article;
use App\Cms\Application\Media;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoMediaQuery implements Media\List\Query\IMediaQuery, Article\Edit\Query\IMediaQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Media\List\Value\Media> */
    public function list(): array
    {
        $media = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, file_name, path, mime_type, file_size, alt_text, caption, created_at FROM media_files ORDER BY created_at DESC')) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $media[] = new Media\List\Value\Media(
                id: $pdoRow->int(key: 'id'),
                fileName: $pdoRow->string(key: 'file_name'),
                path: $pdoRow->string(key: 'path'),
                mimeType: $pdoRow->string(key: 'mime_type'),
                fileSize: $pdoRow->int(key: 'file_size'),
                altText: $pdoRow->string(key: 'alt_text'),
                caption: $pdoRow->string(key: 'caption'),
                createdAt: $pdoRow->string(key: 'created_at'),
            );
        }

        return $media;
    }

    /** @return array<int, Article\Edit\Value\Media> */
    public function media(): array
    {
        $media = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT id, file_name, path, alt_text FROM media_files WHERE mime_type LIKE 'image/%' ORDER BY created_at DESC")) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $media[] = new Article\Edit\Value\Media(
                id: $pdoRow->int(key: 'id'),
                fileName: $pdoRow->string(key: 'file_name'),
                path: $pdoRow->string(key: 'path'),
                altText: $pdoRow->string(key: 'alt_text'),
            );
        }

        return $media;
    }
}
