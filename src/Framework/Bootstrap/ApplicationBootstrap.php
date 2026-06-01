<?php

declare(strict_types=1);

namespace App\Framework\Bootstrap;

use App\Framework\Application\ApplicationInterface;
use App\Framework\Runtime\RuntimeFactory;
use Dotenv\Dotenv;
use RuntimeException;

final readonly class ApplicationBootstrap
{
    private function __construct()
    {
    }

    public static function run(ApplicationBootstrapConfig $config): void
    {
        self::loadEnvironment(config: $config);
        RuntimeFactory::fromEnvironment()->run(application: self::createApplication(config: $config));
    }

    public static function createApplication(ApplicationBootstrapConfig $config): ApplicationInterface
    {
        if ($config->applicationFactory === null) {
            throw new RuntimeException('Application factory is not configured.');
        }

        return ($config->applicationFactory)($config);
    }

    private static function loadEnvironment(ApplicationBootstrapConfig $config): void
    {
        if (!$config->loadDotenv || !is_file($config->root . '/.env')) {
            return;
        }

        Dotenv::createImmutable($config->root)->safeLoad();
    }
}
