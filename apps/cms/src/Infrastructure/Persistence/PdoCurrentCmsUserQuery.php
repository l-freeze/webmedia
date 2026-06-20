<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Auth\CurrentUser\Query\ICurrentCmsUserQuery;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoCurrentCmsUserQuery implements ICurrentCmsUserQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?CurrentCmsUser
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, email, name, role FROM users WHERE id = :id LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        $pdoRow = PdoRow::from(values: $row);

        return new CurrentCmsUser(
            id: $pdoRow->int(key: 'id'),
            email: $pdoRow->string(key: 'email'),
            name: $pdoRow->string(key: 'name'),
            role: $pdoRow->string(key: 'role'),
        );
    }
}
