<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Repository\ITaxonomyRepository;
use App\Framework\Database\PdoHelper;
use PDO;
use Throwable;

final readonly class PdoTaxonomyRepository implements ITaxonomyRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createCategory(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO categories (name, slug, description, created_at, updated_at) VALUES (:name, :slug, :description, :created_at, :updated_at)');
        PdoHelper::execute(statement: $stmt, params: [
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function createTag(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO tags (name, slug, created_at, updated_at) VALUES (:name, :slug, :created_at, :updated_at)');
        PdoHelper::execute(statement: $stmt, params: [
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function updateCategory(int $id, array $data): bool
    {
        if ($this->slugExists(table: 'categories', slug: (string) ($data['slug'] ?? ''), excludeId: $id)) {
            return false;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE categories SET name = :name, slug = :slug, description = :description, updated_at = :updated_at WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: [
            'id' => $id,
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() > 0 || $this->exists(table: 'categories', id: $id);
    }

    public function updateTag(int $id, array $data): bool
    {
        if ($this->slugExists(table: 'tags', slug: (string) ($data['slug'] ?? ''), excludeId: $id)) {
            return false;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE tags SET name = :name, slug = :slug, updated_at = :updated_at WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: [
            'id' => $id,
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() > 0 || $this->exists(table: 'tags', id: $id);
    }

    public function deleteCategory(int $id): bool
    {
        $this->pdo->beginTransaction();

        try {
            $now = date('Y-m-d H:i:s');
            $articles = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE articles SET category_id = NULL, updated_at = :updated_at WHERE category_id = :id');
            PdoHelper::execute(statement: $articles, params: ['id' => $id, 'updated_at' => $now]);

            $versions = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE article_versions SET category_id = NULL WHERE category_id = :id');
            PdoHelper::execute(statement: $versions, params: ['id' => $id]);

            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'DELETE FROM categories WHERE id = :id');
            PdoHelper::execute(statement: $stmt, params: ['id' => $id]);
            $deleted = $stmt->rowCount() > 0;

            $this->pdo->commit();
            return $deleted;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function deleteTag(int $id): bool
    {
        $this->pdo->beginTransaction();

        try {
            $links = PdoHelper::prepare(pdo: $this->pdo, sql: 'DELETE FROM article_tags WHERE tag_id = :id');
            PdoHelper::execute(statement: $links, params: ['id' => $id]);

            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'DELETE FROM tags WHERE id = :id');
            PdoHelper::execute(statement: $stmt, params: ['id' => $id]);
            $deleted = $stmt->rowCount() > 0;

            $this->pdo->commit();
            return $deleted;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function slugExists(string $table, string $slug, int $excludeId): bool
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: "SELECT COUNT(*) FROM {$table} WHERE slug = :slug AND id <> :id");
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug, 'id' => $excludeId]);

        return (int) PdoHelper::fetchColumn(statement: $stmt) > 0;
    }

    private function exists(string $table, int $id): bool
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: "SELECT COUNT(*) FROM {$table} WHERE id = :id");
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);

        return (int) PdoHelper::fetchColumn(statement: $stmt) > 0;
    }
}
