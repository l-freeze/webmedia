<?php

declare(strict_types=1);

namespace App\Kernel\Database\Infrastructure;

use App\Kernel\Environment;
use PDO;

final class ConnectionFactory
{
    private const DEFAULT_PORT = '5432';

    public function __construct(
        private readonly Environment $environment = new Environment(),
    ) {
    }

    public function create(): PDO
    {
        $dsn = $this->environment->string(key: 'DB_DSN', default: '');
        $host = $this->environment->string(key: 'DB_HOST', default: 'db');
        $port = $this->environment->string(key: 'DB_PORT', default: self::DEFAULT_PORT);
        $database = $this->environment->string(key: 'DB_DATABASE', default: 'webmedia');
        $username = $this->environment->string(key: 'DB_USERNAME', default: 'webmedia');
        $password = $this->environment->string(key: 'DB_PASSWORD', default: 'webmedia');

        return new PDO(
            $dsn !== '' ? $dsn : "pgsql:host={$host};port={$port};dbname={$database}",
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
