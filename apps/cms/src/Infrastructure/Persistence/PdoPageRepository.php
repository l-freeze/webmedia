<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Value\PageId;
use PDO;

final readonly class PdoPageRepository implements IPageRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(Page $page): PageId
    {
        $now = date('Y-m-d H:i:s');
        $payload = [
            'title' => $page->title->value,
            'slug' => $page->slug->value,
            'body_markdown' => $page->bodyMarkdown->value,
            'status' => $page->status->value,
            'seo_title' => $page->seoTitle->value,
            'meta_description' => $page->metaDescription->value,
            'updated_at' => $now,
        ];

        if ($page->id === null) {
            $payload['created_at'] = $now;
            $stmt = $this->pdo->prepare('INSERT INTO pages (title, slug, body_markdown, status, seo_title, meta_description, created_at, updated_at) VALUES (:title, :slug, :body_markdown, :status, :seo_title, :meta_description, :created_at, :updated_at)');
            $stmt->execute($payload);
            $id = (int) $this->pdo->lastInsertId();
        } else {
            $id = $page->id->value;
            $payload['id'] = $id;
            $stmt = $this->pdo->prepare('UPDATE pages SET title = :title, slug = :slug, body_markdown = :body_markdown, status = :status, seo_title = :seo_title, meta_description = :meta_description, updated_at = :updated_at WHERE id = :id');
            $stmt->execute($payload);
        }

        $this->snapshot($id, $payload);
        return new PageId($id);
    }

    /** @param array<string, mixed> $payload */
    private function snapshot(int $pageId, array $payload): void
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(version_no), 0) + 1 FROM page_versions WHERE page_id = :id');
        $stmt->execute(['id' => $pageId]);
        $versionNo = (int) $stmt->fetchColumn();
        $insert = $this->pdo->prepare('INSERT INTO page_versions (page_id, version_no, title, body_markdown, status, created_by, created_at) VALUES (:page_id, :version_no, :title, :body_markdown, :status, :created_by, :created_at)');
        $insert->execute([
            'page_id' => $pageId,
            'version_no' => $versionNo,
            'title' => $payload['title'],
            'body_markdown' => $payload['body_markdown'],
            'status' => $payload['status'],
            'created_by' => '編集部',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
