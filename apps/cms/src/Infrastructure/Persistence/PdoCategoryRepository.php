<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\Category;
use App\Cms\Domain\Repository\ICategoryRepository;
use App\Cms\Domain\Value\CategoryId;
use App\Cms\Domain\Value\CategorySlug;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;
use Throwable;

final readonly class PdoCategoryRepository implements ICategoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(Category $category): ?CategoryId
    {
        $now = date('Y-m-d H:i:s');
        $params = [
            'name' => $category->name->value,
            'slug' => $category->slug->value,
            'description' => $category->description->value,
            'updated_at' => $now,
        ];

        if ($category->id === null) {
            $params['created_at'] = $now;
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO categories (name, slug, description, created_at, updated_at) VALUES (:name, :slug, :description, :created_at, :updated_at) RETURNING id');
            PdoHelper::execute(statement: $stmt, params: $params);
            $id = PdoRow::from(values: ['id' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'id');

            return new CategoryId(value: $id);
        }

        $params['id'] = $category->id->value;
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE categories SET name = :name, slug = :slug, description = :description, updated_at = :updated_at WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: $params);

        $stored = $stmt->rowCount() > 0 || $this->exists(id: $category->id);
        return $stored ? $category->id : null;
    }

    public function delete(CategoryId $id): bool
    {
        $this->pdo->beginTransaction();

        try {
            $now = date('Y-m-d H:i:s');
            $articles = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE articles SET category_id = NULL, updated_at = :updated_at WHERE category_id = :id');
            PdoHelper::execute(statement: $articles, params: ['id' => $id->value, 'updated_at' => $now]);

            $versions = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE article_versions SET category_id = NULL WHERE category_id = :id');
            PdoHelper::execute(statement: $versions, params: ['id' => $id->value]);

            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'DELETE FROM categories WHERE id = :id');
            PdoHelper::execute(statement: $stmt, params: ['id' => $id->value]);
            $deleted = $stmt->rowCount() > 0;

            $this->pdo->commit();
            return $deleted;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function slugExists(CategorySlug $slug, ?CategoryId $exceptId): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
        $params = ['slug' => $slug->value];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId->value;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $sql);
        PdoHelper::execute(statement: $stmt, params: $params);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }

    private function exists(CategoryId $id): bool
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT COUNT(*) FROM categories WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id->value]);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }
}
