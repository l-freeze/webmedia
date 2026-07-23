<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\User;
use App\Cms\Domain\Repository\IUserRepository;
use App\Cms\Domain\Value\AdminUserEmail;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\AdminUserName;
use App\Cms\Domain\Value\CmsRole;
use App\Cms\Domain\Value\PasswordHash;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoUserRepository implements IUserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(AdminUserEmail $email): ?User
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, email, password_hash, name, role FROM users WHERE email = :email LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['email' => $email->value]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        $pdoRow = PdoRow::from(values: $row);

        return new User(
            id: new AdminUserId(value: $pdoRow->int(key: 'id')),
            email: new AdminUserEmail(value: $pdoRow->string(key: 'email')),
            passwordHash: new PasswordHash(value: $pdoRow->string(key: 'password_hash')),
            name: new AdminUserName(value: $pdoRow->string(key: 'name')),
            role: CmsRole::from($pdoRow->string(key: 'role')),
        );
    }
}
