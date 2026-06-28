<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Page\Edit\Query\IPageQuery;
use App\Cms\Application\Page\Edit\Value\Page;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoPageEditQuery implements IPageQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?Page
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT * FROM pages WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        $pdoRow = PdoRow::from(values: $row);

        return new Page(
            id: $pdoRow->int(key: 'id'),
            title: $pdoRow->string(key: 'title'),
            slug: $pdoRow->string(key: 'slug'),
            body: $pdoRow->string(key: 'body'),
            bodyFormat: $pdoRow->string(key: 'body_format', default: 'markdown'),
            status: $pdoRow->string(key: 'status'),
            displayOrder: $pdoRow->int(key: 'display_order'),
            seoTitle: $pdoRow->string(key: 'seo_title'),
            metaDescription: $pdoRow->string(key: 'meta_description'),
        );
    }
}
