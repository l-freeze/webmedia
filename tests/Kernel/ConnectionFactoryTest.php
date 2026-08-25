<?php

declare(strict_types=1);

namespace Tests\Kernel;

use App\Kernel\Database\Infrastructure\ConnectionFactory;
use PDO;
use PHPUnit\Framework\TestCase;

final class ConnectionFactoryTest extends TestCase
{
    private string|false $originalProcessDsn;

    private bool $originalEnvDsnExists;

    private mixed $originalEnvDsn;

    private bool $originalServerDsnExists;

    private mixed $originalServerDsn;

    protected function setUp(): void
    {
        $this->originalProcessDsn = getenv('DB_DSN');
        $this->originalEnvDsnExists = array_key_exists('DB_DSN', $_ENV);
        $this->originalEnvDsn = $_ENV['DB_DSN'] ?? null;
        $this->originalServerDsnExists = array_key_exists('DB_DSN', $_SERVER);
        $this->originalServerDsn = $_SERVER['DB_DSN'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalProcessDsn === false) {
            putenv('DB_DSN');
        } else {
            putenv('DB_DSN=' . $this->originalProcessDsn);
        }

        if ($this->originalEnvDsnExists) {
            $_ENV['DB_DSN'] = $this->originalEnvDsn;
        } else {
            unset($_ENV['DB_DSN']);
        }

        if ($this->originalServerDsnExists) {
            $_SERVER['DB_DSN'] = $this->originalServerDsn;
        } else {
            unset($_SERVER['DB_DSN']);
        }
    }

    public function testCreatesConnectionFromDotenvPopulatedEnvironment(): void
    {
        putenv('DB_DSN');
        $_ENV['DB_DSN'] = 'sqlite::memory:';
        unset($_SERVER['DB_DSN']);

        $connection = (new ConnectionFactory())->create();

        self::assertSame('sqlite', $connection->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function testProcessDsnWinsOverDotenvPopulatedEnvironment(): void
    {
        putenv('DB_DSN=sqlite::memory:');
        $_ENV['DB_DSN'] = 'unsupported:dotenv';
        $_SERVER['DB_DSN'] = 'unsupported:server';

        $connection = (new ConnectionFactory())->create();

        self::assertSame('sqlite', $connection->getAttribute(PDO::ATTR_DRIVER_NAME));
    }
}
