<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\User;
use App\Cms\Domain\Repository\IUserRepository;
use App\Framework\Database\PdoHelper;
use PDO;

final readonly class PdoUserRepository implements IUserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, email, password_hash, name, role FROM users WHERE email = :email LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['email' => $email]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        return new User(
            id: (int) $row['id'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            name: (string) $row['name'],
            role: (string) $row['role'],
        );
    }
}
