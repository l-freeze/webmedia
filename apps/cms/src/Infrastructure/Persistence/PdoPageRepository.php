<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Value\MetaDescription;
use App\Cms\Domain\Value\PageBody;
use App\Cms\Domain\Value\PageBodyFormat;
use App\Cms\Domain\Value\PageDisplayOrder;
use App\Cms\Domain\Value\PageId;
use App\Cms\Domain\Value\PageSlug;
use App\Cms\Domain\Value\PageStatus;
use App\Cms\Domain\Value\PageTitle;
use App\Cms\Domain\Value\PageVersionNo;
use App\Cms\Domain\Value\SeoTitle;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use App\Shared\Markdown\Infrastructure\Markdown;
use PDO;
use Throwable;

final readonly class PdoPageRepository implements IPageRepository
{
    public function __construct(private PDO $pdo, private Markdown $markdown)
    {
    }

    public function save(Page $page): PageId
    {
        $this->pdo->beginTransaction();

        try {
            $pageId = $this->saveWithinTransaction(page: $page);
            $this->pdo->commit();
            return $pageId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function slugExists(string $slug, ?int $exceptId): bool
    {
        $sql = 'SELECT COUNT(*) FROM pages WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $sql);
        PdoHelper::execute(statement: $stmt, params: $params);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }

    public function restore(PageId $pageId, PageVersionNo $versionNo): void
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT * FROM page_versions WHERE page_id = :id AND version_no = :version_no');
        PdoHelper::execute(statement: $stmt, params: ['id' => $pageId->value, 'version_no' => $versionNo->value]);
        $version = PdoHelper::fetch(statement: $stmt);
        if ($version === null) {
            return;
        }

        $this->save(page: $this->pageFromVersionRow(pageId: $pageId, row: $version));
    }

    private function saveWithinTransaction(Page $page): PageId
    {
        $now = date('Y-m-d H:i:s');
        $payload = [
            'title' => $page->title->value,
            'slug' => $page->slug->value,
            'body' => $page->body->value,
            'body_format' => $page->bodyFormat->value,
            'rendered_body_html' => $this->markdown->bodyToSafeHtml(body: $page->body->value, format: $page->bodyFormat->value),
            'status' => $page->status->value,
            'display_order' => $page->displayOrder->value,
            'seo_title' => $page->seoTitle->value,
            'meta_description' => $page->metaDescription->value,
            'updated_at' => $now,
        ];

        if ($page->id === null) {
            $payload['created_at'] = $now;
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO pages (title, slug, body, body_format, rendered_body_html, status, display_order, seo_title, meta_description, created_at, updated_at) VALUES (:title, :slug, :body, :body_format, :rendered_body_html, :status, :display_order, :seo_title, :meta_description, :created_at, :updated_at) RETURNING id');
            PdoHelper::execute(statement: $stmt, params: $payload);
            $id = PdoRow::from(values: ['id' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'id');
        } else {
            $id = $page->id->value;
            $payload['id'] = $id;
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE pages SET title = :title, slug = :slug, body = :body, body_format = :body_format, rendered_body_html = :rendered_body_html, status = :status, display_order = :display_order, seo_title = :seo_title, meta_description = :meta_description, updated_at = :updated_at WHERE id = :id');
            PdoHelper::execute(statement: $stmt, params: $payload);
        }

        $versionNo = $page->id === null ? 1 : $this->nextVersionNo(pageId: $id);
        $this->snapshot(pageId: $id, versionNo: $versionNo, payload: $payload);
        $this->updateCurrentVersionNo(pageId: $id, versionNo: $versionNo);

        return new PageId(value: $id);
    }

    /** @param array<string, mixed> $payload */
    private function snapshot(int $pageId, int $versionNo, array $payload): void
    {
        $insert = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO page_versions (page_id, version_no, title, slug, body, body_format, status, display_order, seo_title, meta_description, created_by, created_at) VALUES (:page_id, :version_no, :title, :slug, :body, :body_format, :status, :display_order, :seo_title, :meta_description, :created_by, :created_at)');
        PdoHelper::execute(statement: $insert, params: [
            'page_id' => $pageId,
            'version_no' => $versionNo,
            'title' => $payload['title'],
            'slug' => $payload['slug'],
            'body' => $payload['body'],
            'body_format' => $payload['body_format'],
            'status' => $payload['status'],
            'display_order' => $payload['display_order'],
            'seo_title' => $payload['seo_title'],
            'meta_description' => $payload['meta_description'],
            'created_by' => '編集部',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function nextVersionNo(int $pageId): int
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT current_version_no FROM pages WHERE id = :id FOR UPDATE');
        PdoHelper::execute(statement: $stmt, params: ['id' => $pageId]);
        $currentVersionNo = PdoRow::from(values: ['version_no' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'version_no');

        $maxStmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT COALESCE(MAX(version_no), 0) FROM page_versions WHERE page_id = :id');
        PdoHelper::execute(statement: $maxStmt, params: ['id' => $pageId]);
        $maxVersionNo = PdoRow::from(values: ['version_no' => PdoHelper::fetchColumn(statement: $maxStmt)])->int(key: 'version_no');

        return max($currentVersionNo, $maxVersionNo) + 1;
    }

    private function updateCurrentVersionNo(int $pageId, int $versionNo): void
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE pages SET current_version_no = :version_no WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['version_no' => $versionNo, 'id' => $pageId]);
    }

    /** @param array<string, mixed> $row */
    private function pageFromVersionRow(PageId $pageId, array $row): Page
    {
        $version = PdoRow::from(values: $row);

        return new Page(
            id: $pageId,
            title: new PageTitle(value: $version->string(key: 'title')),
            slug: new PageSlug(value: $version->string(key: 'slug')),
            body: new PageBody(value: $version->string(key: 'body')),
            bodyFormat: new PageBodyFormat(value: $version->string(key: 'body_format', default: PageBodyFormat::MARKDOWN)),
            status: new PageStatus(value: $version->string(key: 'status', default: 'draft')),
            displayOrder: new PageDisplayOrder(value: $version->int(key: 'display_order')),
            seoTitle: new SeoTitle(value: $version->string(key: 'seo_title')),
            metaDescription: new MetaDescription(value: $version->string(key: 'meta_description')),
        );
    }
}
