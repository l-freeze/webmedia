<?php

declare(strict_types=1);

namespace App\Cms\Auth\Infrastructure\Persistence;

use App\Cms\Auth\Domain\UserRepository;
use PDO;

final readonly class PdoUserRepository implements UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, name, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
