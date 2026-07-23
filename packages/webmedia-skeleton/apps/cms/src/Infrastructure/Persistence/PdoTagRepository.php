<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\Tag;
use App\Cms\Domain\Repository\ITagRepository;
use App\Cms\Domain\Value\TagId;
use App\Cms\Domain\Value\TagSlug;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;
use Throwable;

final readonly class PdoTagRepository implements ITagRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(Tag $tag): ?TagId
    {
        $now = date('Y-m-d H:i:s');
        $params = [
            'name' => $tag->name->value,
            'slug' => $tag->slug->value,
            'updated_at' => $now,
        ];

        if ($tag->id === null) {
            $params['created_at'] = $now;
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO tags (name, slug, created_at, updated_at) VALUES (:name, :slug, :created_at, :updated_at) RETURNING id');
            PdoHelper::execute(statement: $stmt, params: $params);
            $id = PdoRow::from(values: ['id' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'id');

            return new TagId(value: $id);
        }

        $params['id'] = $tag->id->value;
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE tags SET name = :name, slug = :slug, updated_at = :updated_at WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: $params);

        $stored = $stmt->rowCount() > 0 || $this->exists(id: $tag->id);
        return $stored ? $tag->id : null;
    }

    public function delete(TagId $id): bool
    {
        $this->pdo->beginTransaction();

        try {
            $links = PdoHelper::prepare(pdo: $this->pdo, sql: 'DELETE FROM article_tags WHERE tag_id = :id');
            PdoHelper::execute(statement: $links, params: ['id' => $id->value]);

            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'DELETE FROM tags WHERE id = :id');
            PdoHelper::execute(statement: $stmt, params: ['id' => $id->value]);
            $deleted = $stmt->rowCount() > 0;

            $this->pdo->commit();
            return $deleted;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function slugExists(TagSlug $slug, ?TagId $exceptId): bool
    {
        $sql = 'SELECT COUNT(*) FROM tags WHERE slug = :slug';
        $params = ['slug' => $slug->value];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId->value;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $sql);
        PdoHelper::execute(statement: $stmt, params: $params);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }

    private function exists(TagId $id): bool
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT COUNT(*) FROM tags WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id->value]);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }
}
