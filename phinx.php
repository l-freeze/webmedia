<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'webmedia';
$username = getenv('DB_USERNAME') ?: 'webmedia';
$password = getenv('DB_PASSWORD') ?: 'webmedia';

return [
    'paths' => [
        'migrations' => 'database/migrations',
        'seeds' => 'database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => $host,
            'port' => $port,
            'name' => $database,
            'user' => $username,
            'pass' => $password,
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
