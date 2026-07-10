<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Repository\ICategoryRepository;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;
use Throwable;

final readonly class PdoCategoryRepository implements ICategoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @param array{name:mixed,slug:mixed,description?:mixed} $data */
    public function create(array $data): void
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

    /** @param array{name:mixed,slug:mixed,description?:mixed} $data */
    public function update(int $id, array $data): bool
    {
        $input = PdoRow::from(values: $data);
        if ($this->slugExists(slug: $input->string(key: 'slug'), exceptId: $id)) {
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

        return $stmt->rowCount() > 0 || $this->exists(id: $id);
    }

    public function delete(int $id): bool
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

    public function slugExists(string $slug, ?int $exceptId): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $sql);
        PdoHelper::execute(statement: $stmt, params: $params);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }

    private function exists(int $id): bool
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT COUNT(*) FROM categories WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }
}
