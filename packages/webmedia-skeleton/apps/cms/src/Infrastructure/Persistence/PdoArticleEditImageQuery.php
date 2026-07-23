<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Edit\Query\IImageQuery;
use App\Cms\Application\Article\Edit\Value\Image;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoArticleEditImageQuery implements IImageQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Image> */
    public function images(): array
    {
        $images = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT id, file_name, path, alt_text, width, height, thumbnail_path, optimized_path FROM media_files WHERE mime_type LIKE 'image/%' ORDER BY created_at DESC")) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $images[] = new Image(
                id: $pdoRow->int(key: 'id'),
                fileName: $pdoRow->string(key: 'file_name'),
                path: $pdoRow->string(key: 'path'),
                altText: $pdoRow->string(key: 'alt_text'),
                width: $pdoRow->nullableInt(key: 'width'),
                height: $pdoRow->nullableInt(key: 'height'),
                thumbnailPath: $pdoRow->nullableString(key: 'thumbnail_path'),
                optimizedPath: $pdoRow->nullableString(key: 'optimized_path'),
            );
        }

        return $images;
    }
}
