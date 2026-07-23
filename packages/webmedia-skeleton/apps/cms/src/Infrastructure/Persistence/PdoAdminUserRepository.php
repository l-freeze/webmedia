<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\AdminUser\List\Query\IAdminUserQuery;
use App\Cms\Application\AdminUser\List\Value\AdminUser;
use App\Cms\Domain\Entity\AdminUser as AdminUserEntity;
use App\Cms\Domain\Repository\IAdminUserRepository;
use App\Cms\Domain\Value\AdminUserEmail;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\CmsRole;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoAdminUserRepository implements IAdminUserRepository, IAdminUserQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<AdminUser> */
    public function users(): array
    {
        $users = [];
        $stmt = PdoHelper::query(
            pdo: $this->pdo,
            sql: 'SELECT id, email, name, role, created_at, updated_at FROM users ORDER BY id ASC',
        );

        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $pdoRow = PdoRow::from(values: $row);
            $users[] = new AdminUser(
                id: $pdoRow->int(key: 'id'),
                email: $pdoRow->string(key: 'email'),
                name: $pdoRow->string(key: 'name'),
                role: $pdoRow->string(key: 'role'),
                createdAt: $pdoRow->string(key: 'created_at'),
                updatedAt: $pdoRow->string(key: 'updated_at'),
            );
        }

        return $users;
    }

    public function create(AdminUserEntity $user): AdminUserId
    {
        $now = date('Y-m-d H:i:s');
        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: 'INSERT INTO users (email, password_hash, name, role, created_at, updated_at) VALUES (:email, :password_hash, :name, :role, :created_at, :updated_at)',
        );
        PdoHelper::execute(statement: $stmt, params: [
            'email' => $user->email->value,
            'password_hash' => $user->passwordHash->value,
            'name' => $user->name->value,
            'role' => $user->role->value,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return new AdminUserId(value: (int) $this->pdo->lastInsertId());
    }

    public function update(AdminUserEntity $user): bool
    {
        if ($user->id === null) {
            return false;
        }

        $params = [
            'id' => $user->id->value,
            'email' => $user->email->value,
            'name' => $user->name->value,
            'role' => $user->role->value,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $passwordSql = '';

        if ($user->passwordHash->value !== '') {
            $passwordSql = ', password_hash = :password_hash';
            $params['password_hash'] = $user->passwordHash->value;
        }

        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: "UPDATE users SET email = :email, name = :name, role = :role{$passwordSql}, updated_at = :updated_at WHERE id = :id",
        );
        PdoHelper::execute(statement: $stmt, params: $params);

        return $stmt->rowCount() > 0 || $this->roleOf(id: $user->id) !== null;
    }

    public function delete(AdminUserId $id): bool
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'DELETE FROM users WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id->value]);

        return $stmt->rowCount() > 0;
    }

    public function emailExists(AdminUserEmail $email, ?AdminUserId $exceptId): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email->value];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId->value;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $sql);
        PdoHelper::execute(statement: $stmt, params: $params);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }

    public function roleOf(AdminUserId $id): ?CmsRole
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT role FROM users WHERE id = :id LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id->value]);
        $role = PdoHelper::fetchColumn(statement: $stmt);

        return is_scalar($role) ? CmsRole::from((string) $role) : null;
    }

    public function adminCount(): int
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT COUNT(*) FROM users WHERE role = :role');
        PdoHelper::execute(statement: $stmt, params: ['role' => CmsRole::Admin->value]);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count');
    }
}
