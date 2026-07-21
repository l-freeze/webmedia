<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Infrastructure\Database;

use PDO;

final readonly class ConnectionFactory
{
    public function __construct(
        private string $dsn,
        private ?string $username = null,
        private ?string $password = null,
    ) {
    }

    public function create(): PDO
    {
        return new PDO(
            dsn: $this->dsn,
            username: $this->username,
            password: $this->password,
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
}
