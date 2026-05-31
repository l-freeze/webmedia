<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Page\History\Query\IPageHistoryQuery;
use App\Cms\Application\Page\History\Value\PageVersion;
use App\Framework\Database\PdoHelper;
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
        return array_map(fn (array $row): PageVersion => new PageVersion(
            versionNo: (int) $row['version_no'],
            title: (string) $row['title'],
            status: (string) $row['status'],
            createdAt: (string) $row['created_at'],
        ), PdoHelper::fetchAll(statement: $stmt));
    }
}
