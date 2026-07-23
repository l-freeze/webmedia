<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Shared\Markdown\Infrastructure\Markdown;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use App\Front\Application\Page\Show\Query\IPageQuery;
use App\Front\Application\Page\Show\Value\PageDetail;
use PDO;

final readonly class PdoPageQuery implements IPageQuery
{
    public function __construct(private PDO $pdo, private Markdown $markdown)
    {
    }

    public function findPublishedBySlug(string $slug): ?PageDetail
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: "SELECT * FROM pages WHERE slug = :slug AND status = 'published' LIMIT 1");
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }
        $pdoRow = PdoRow::from(values: $row);

        return new PageDetail(
            id: $pdoRow->int(key: 'id'),
            title: $pdoRow->string(key: 'title'),
            slug: $pdoRow->string(key: 'slug'),
            bodyHtml: $this->bodyHtml(row: $pdoRow),
            updatedAt: $pdoRow->date(key: 'updated_at'),
        );
    }

    private function bodyHtml(PdoRow $row): string
    {
        $bodyHtml = $row->string(key: 'rendered_body_html');
        if ($bodyHtml !== '') {
            return $bodyHtml;
        }

        return $this->markdown->bodyToSafeHtml(
            body: $row->string(key: 'body'),
            format: $row->string(key: 'body_format', default: 'markdown'),
        );
    }
}
