<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Page\History\Query\IPageHistoryQuery;
use App\Cms\Application\Page\History\Value\PageVersion;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
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
        $versions = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $versions[] = new PageVersion(
                versionNo: $pdoRow->int(key: 'version_no'),
                title: $pdoRow->string(key: 'title'),
                status: $pdoRow->string(key: 'status'),
                createdAt: $pdoRow->string(key: 'created_at'),
            );
        }

        return $versions;
    }
}
