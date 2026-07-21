<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Infrastructure\Persistence;

use App\{{ namespace }}\Application\Todo\TodoItem;
use App\{{ namespace }}\Application\Todo\ITodoRepository;
use PDO;
use PDOStatement;
use RuntimeException;
use UnexpectedValueException;

final readonly class PdoTodoRepository implements ITodoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<TodoItem> */
    public function all(): array
    {
        $this->ensureSchema();

        $items = [];
        $statement = $this->query(sql: 'SELECT id, title, completed, created_at FROM todos ORDER BY completed ASC, created_at DESC');
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (!is_array($row)) {
                throw new UnexpectedValueException('Todo row must be an associative array.');
            }
            $values = [];
            foreach ($row as $key => $value) {
                if (is_string($key)) {
                    $values[$key] = $value;
                }
            }
            $items[] = $this->item(row: $values);
        }

        return $items;
    }

    public function add(string $title): void
    {
        $this->ensureSchema();

        $statement = $this->prepare(sql: 'INSERT INTO todos (id, title, completed, created_at) VALUES (:id, :title, 0, :created_at)');
        $this->execute(statement: $statement, params: [
            'id' => bin2hex(random_bytes(16)),
            'title' => $title,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function complete(string $id): void
    {
        $this->ensureSchema();

        $statement = $this->prepare(sql: 'UPDATE todos SET completed = 1 WHERE id = :id');
        $this->execute(statement: $statement, params: ['id' => $id]);
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todos (id VARCHAR(32) PRIMARY KEY, title VARCHAR(255) NOT NULL, completed SMALLINT NOT NULL DEFAULT 0, created_at VARCHAR(32) NOT NULL)');
    }

    /** @param array<string, mixed> $row */
    private function item(array $row): TodoItem
    {
        $id = $row['id'] ?? null;
        $title = $row['title'] ?? null;
        $completed = $row['completed'] ?? null;
        $createdAt = $row['created_at'] ?? null;
        if (!is_string($id) || !is_string($title) || !is_string($createdAt)) {
            throw new UnexpectedValueException('Todo row contains invalid string values.');
        }
        if (!is_int($completed) && !is_string($completed)) {
            throw new UnexpectedValueException('Todo row contains an invalid completed value.');
        }

        return new TodoItem(
            id: $id,
            title: $title,
            completed: (int) $completed === 1,
            createdAt: $createdAt,
        );
    }

    private function query(string $sql): PDOStatement
    {
        $statement = $this->pdo->query($sql);
        if (!$statement instanceof PDOStatement) {
            throw new RuntimeException('Failed to execute todo query.');
        }

        return $statement;
    }

    private function prepare(string $sql): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        if (!$statement instanceof PDOStatement) {
            throw new RuntimeException('Failed to prepare todo statement.');
        }

        return $statement;
    }

    /** @param array<string, mixed> $params */
    private function execute(PDOStatement $statement, array $params): void
    {
        if (!$statement->execute($params)) {
            throw new RuntimeException('Failed to execute todo statement.');
        }
    }
}
