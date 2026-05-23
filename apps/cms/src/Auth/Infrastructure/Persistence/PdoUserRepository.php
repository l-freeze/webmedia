<?php

declare(strict_types=1);

namespace App\Cms\Auth\Infrastructure\Persistence;

use App\Cms\Auth\Domain\IUserRepository;
use App\Cms\Auth\Domain\Entity\User;
use PDO;

final readonly class PdoUserRepository implements IUserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, name, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        if (!$row) {
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
