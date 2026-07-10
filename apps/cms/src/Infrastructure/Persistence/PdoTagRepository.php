<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Repository\ITagRepository;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;
use Throwable;

final readonly class PdoTagRepository implements ITagRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @param array{name:mixed,slug:mixed} $data */
    public function create(array $data): void
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

    /** @param array{name:mixed,slug:mixed} $data */
    public function update(int $id, array $data): bool
    {
        $input = PdoRow::from(values: $data);
        if ($this->slugExists(slug: $input->string(key: 'slug'), exceptId: $id)) {
            return false;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE tags SET name = :name, slug = :slug, updated_at = :updated_at WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: [
            'id' => $id,
            'name' => $input->string(key: 'name'),
            'slug' => $input->string(key: 'slug'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() > 0 || $this->exists(id: $id);
    }

    public function delete(int $id): bool
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

    public function slugExists(string $slug, ?int $exceptId): bool
    {
        $sql = 'SELECT COUNT(*) FROM tags WHERE slug = :slug';
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
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT COUNT(*) FROM tags WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }
}
