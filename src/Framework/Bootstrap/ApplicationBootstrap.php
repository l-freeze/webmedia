<?php

declare(strict_types=1);

namespace App\Framework\Bootstrap;

use App\Framework\Application\IApplication;
use App\Framework\Runtime\RuntimeFactory;
use RuntimeException;

final readonly class ApplicationBootstrap
{
    private function __construct()
    {
    }

    public static function run(ApplicationBootstrapConfig $config): void
    {
        RuntimeFactory::fromEnvironment()->run(application: self::createApplication(config: $config));
    }

    public static function createApplication(ApplicationBootstrapConfig $config): IApplication
    {
        if ($config->applicationFactory === null) {
            throw new RuntimeException('Application factory is not configured.');
        }

        return ($config->applicationFactory)($config);
    }

}
