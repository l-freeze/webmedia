<?php

declare(strict_types=1);

namespace App\Shared\Database;

use PDO;
use PDOStatement;
use RuntimeException;

final readonly class PdoHelper
{
    private function __construct()
    {
    }

    public static function prepare(PDO $pdo, string $sql): PDOStatement
    {
        $statement = $pdo->prepare($sql);
        if (!$statement instanceof PDOStatement) {
            throw new RuntimeException('Failed to prepare SQL statement.');
        }

        return $statement;
    }

    public static function query(PDO $pdo, string $sql): PDOStatement
    {
        $statement = $pdo->query($sql);
        if (!$statement instanceof PDOStatement) {
            throw new RuntimeException('Failed to execute SQL query.');
        }

        return $statement;
    }

    /** @param array<string, mixed> $params */
    public static function execute(PDOStatement $statement, array $params = []): void
    {
        $executed = $params === [] ? $statement->execute() : $statement->execute($params);
        if (!$executed) {
            throw new RuntimeException('Failed to execute SQL statement.');
        }
    }

    /** @return array<string, mixed>|null */
    public static function fetch(PDOStatement $statement): ?array
    {
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        if (!is_array($row)) {
            throw new RuntimeException('Failed to fetch associative row.');
        }

        /** @var array<string, mixed> $row */
        return $row;
    }

    /** @return list<array<string, mixed>> */
    public static function fetchAll(PDOStatement $statement): array
    {
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }

    public static function fetchColumn(PDOStatement $statement): mixed
    {
        $value = $statement->fetchColumn();
        return $value === false ? null : $value;
    }
}
