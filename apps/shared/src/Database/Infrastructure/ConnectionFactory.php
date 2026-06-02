<?php

declare(strict_types=1);

namespace App\Shared\Database\Infrastructure;

use PDO;

final class ConnectionFactory
{
    public function create(): PDO
    {
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '5432';
        $database = getenv('DB_DATABASE') ?: 'webmedia';
        $username = getenv('DB_USERNAME') ?: 'webmedia';
        $password = getenv('DB_PASSWORD') ?: 'webmedia';

        return new PDO(
            "pgsql:host={$host};port={$port};dbname={$database}",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
}
