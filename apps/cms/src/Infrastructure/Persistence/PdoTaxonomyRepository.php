<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Repository\ITaxonomyRepository;
use App\Framework\Database\PdoHelper;
use App\Framework\Database\PdoRow;
use PDO;
use Throwable;

final readonly class PdoTaxonomyRepository implements ITaxonomyRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createCategory(array $data): void
    {
        $input = PdoRow::from(values: $data);
        $now = date('Y-m-d H:i:s');
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO categories (name, slug, description, created_at, updated_at) VALUES (:name, :slug, :description, :created_at, :updated_at)');
        PdoHelper::execute(statement: $stmt, params: [
            'name' => $input->string(key: 'name'),
            'slug' => $input->string(key: 'slug'),
            'description' => $input->string(key: 'description'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function createTag(array $data): void
    {
        $input = PdoRow::from(values: $data);
        $now = date('Y-m-d H:i:s');
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO tags (name, slug, created_at, updated_at) VALUES (:name, :slug, :created_at, :updated_at)');
        PdoHelper::execute(statement: $stmt, params: [
            'name' => $input->string(key: 'name'),
            'slug' => $input->string(key: 'slug'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function updateCategory(int $id, array $data): bool
    {
        $input = PdoRow::from(values: $data);
        if ($this->slugExists(table: 'categories', slug: $input->string(key: 'slug'), excludeId: $id)) {
            return false;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE categories SET name = :name, slug = :slug, description = :description, updated_at = :updated_at WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: [
            'id' => $id,
            'name' => $input->string(key: 'name'),
            'slug' => $input->string(key: 'slug'),
            'description' => $input->string(key: 'description'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() > 0 || $this->exists(table: 'categories', id: $id);
    }

    public function updateTag(int $id, array $data): bool
    {
        $input = PdoRow::from(values: $data);
        if ($this->slugExists(table: 'tags', slug: $input->string(key: 'slug'), excludeId: $id)) {
            return false;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE tags SET name = :name, slug = :slug, updated_at = :updated_at WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: [
            'id' => $id,
            'name' => $input->string(key: 'name'),
            'slug' => $input->string(key: 'slug'),
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

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }

    private function exists(string $table, int $id): bool
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: "SELECT COUNT(*) FROM {$table} WHERE id = :id");
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }
}
