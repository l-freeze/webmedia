<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Value\PageId;
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
            $pageId = $this->saveWithinTransaction($page);
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
            $stmt = $this->pdo->prepare('INSERT INTO pages (title, slug, body_markdown, status, display_order, seo_title, meta_description, created_at, updated_at) VALUES (:title, :slug, :body_markdown, :status, :display_order, :seo_title, :meta_description, :created_at, :updated_at) RETURNING id');
            $stmt->execute($payload);
            $id = (int) $stmt->fetchColumn();
        } else {
            $id = $page->id->value;
            $payload['id'] = $id;
            $stmt = $this->pdo->prepare('UPDATE pages SET title = :title, slug = :slug, body_markdown = :body_markdown, status = :status, display_order = :display_order, seo_title = :seo_title, meta_description = :meta_description, updated_at = :updated_at WHERE id = :id');
            $stmt->execute($payload);
        }

        $versionNo = $page->id === null ? 1 : $this->nextVersionNo($id);
        $this->snapshot($id, $versionNo, $payload);
        $this->updateCurrentVersionNo($id, $versionNo);

        return new PageId($id);
    }

    /** @param array<string, mixed> $payload */
    private function snapshot(int $pageId, int $versionNo, array $payload): void
    {
        $insert = $this->pdo->prepare('INSERT INTO page_versions (page_id, version_no, title, slug, body_markdown, status, display_order, seo_title, meta_description, created_by, created_at) VALUES (:page_id, :version_no, :title, :slug, :body_markdown, :status, :display_order, :seo_title, :meta_description, :created_by, :created_at)');
        $insert->execute([
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
        $stmt = $this->pdo->prepare('SELECT current_version_no FROM pages WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $pageId]);
        $currentVersionNo = (int) $stmt->fetchColumn();

        $maxStmt = $this->pdo->prepare('SELECT COALESCE(MAX(version_no), 0) FROM page_versions WHERE page_id = :id');
        $maxStmt->execute(['id' => $pageId]);
        $maxVersionNo = (int) $maxStmt->fetchColumn();

        return max($currentVersionNo, $maxVersionNo) + 1;
    }

    private function updateCurrentVersionNo(int $pageId, int $versionNo): void
    {
        $stmt = $this->pdo->prepare('UPDATE pages SET current_version_no = :version_no WHERE id = :id');
        $stmt->execute(['version_no' => $versionNo, 'id' => $pageId]);
    }
}
