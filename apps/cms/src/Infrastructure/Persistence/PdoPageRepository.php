<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Value\PageId;
use App\Framework\Database\PdoHelper;
use PDO;
use Throwable;

final readonly class PdoPageRepository implements IPageRepository
{
    public function __construct(private PDO $pdo)
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

    private function saveWithinTransaction(Page $page): PageId
    {
        $now = date('Y-m-d H:i:s');
        $payload = [
            'title' => $page->title->value,
            'slug' => $page->slug->value,
            'body_markdown' => $page->bodyMarkdown->value,
            'status' => $page->status->value,
            'display_order' => $page->displayOrder->value,
            'seo_title' => $page->seoTitle->value,
            'meta_description' => $page->metaDescription->value,
            'updated_at' => $now,
        ];

        if ($page->id === null) {
            $payload['created_at'] = $now;
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO pages (title, slug, body_markdown, status, display_order, seo_title, meta_description, created_at, updated_at) VALUES (:title, :slug, :body_markdown, :status, :display_order, :seo_title, :meta_description, :created_at, :updated_at) RETURNING id');
            PdoHelper::execute(statement: $stmt, params: $payload);
            $id = (int) PdoHelper::fetchColumn(statement: $stmt);
        } else {
            $id = $page->id->value;
            $payload['id'] = $id;
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE pages SET title = :title, slug = :slug, body_markdown = :body_markdown, status = :status, display_order = :display_order, seo_title = :seo_title, meta_description = :meta_description, updated_at = :updated_at WHERE id = :id');
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
        $insert = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO page_versions (page_id, version_no, title, slug, body_markdown, status, display_order, seo_title, meta_description, created_by, created_at) VALUES (:page_id, :version_no, :title, :slug, :body_markdown, :status, :display_order, :seo_title, :meta_description, :created_by, :created_at)');
        PdoHelper::execute(statement: $insert, params: [
            'page_id' => $pageId,
            'version_no' => $versionNo,
            'title' => $payload['title'],
            'slug' => $payload['slug'],
            'body_markdown' => $payload['body_markdown'],
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
        $currentVersionNo = (int) PdoHelper::fetchColumn(statement: $stmt);

        $maxStmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT COALESCE(MAX(version_no), 0) FROM page_versions WHERE page_id = :id');
        PdoHelper::execute(statement: $maxStmt, params: ['id' => $pageId]);
        $maxVersionNo = (int) PdoHelper::fetchColumn(statement: $maxStmt);

        return max($currentVersionNo, $maxVersionNo) + 1;
    }

    private function updateCurrentVersionNo(int $pageId, int $versionNo): void
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE pages SET current_version_no = :version_no WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['version_no' => $versionNo, 'id' => $pageId]);
    }
}
