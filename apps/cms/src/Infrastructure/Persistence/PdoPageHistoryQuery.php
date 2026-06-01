<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Page\History\Query\IPageHistoryQuery;
use App\Cms\Application\Page\History\Value\PageVersion;
use App\Framework\Database\PdoHelper;
use App\Framework\Database\PdoRow;
use PDO;

final readonly class PdoPageHistoryQuery implements IPageHistoryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, PageVersion> */
    public function versions(int $pageId): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT version_no, title, status, created_at FROM page_versions WHERE page_id = :id ORDER BY version_no DESC');
        PdoHelper::execute(statement: $stmt, params: ['id' => $pageId]);
        return array_map(static function (array $row): PageVersion {
            $pdoRow = PdoRow::from(values: $row);

            return new PageVersion(
                versionNo: $pdoRow->int(key: 'version_no'),
                title: $pdoRow->string(key: 'title'),
                status: $pdoRow->string(key: 'status'),
                createdAt: $pdoRow->string(key: 'created_at'),
            );
        }, PdoHelper::fetchAll(statement: $stmt));
    }
}
