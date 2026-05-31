<?php

declare(strict_types=1);

namespace App\Framework\Bootstrap;

use App\Framework\Application\Application;
use App\Framework\Http\ExceptionHandling\RethrowingExceptionHandler;
use App\Framework\Http\Middleware\MiddlewarePipeline;
use App\Framework\Runtime\RuntimeFactory;
use App\Framework\Security\NativeSessionRuntime;
use Dotenv\Dotenv;

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

    public static function createApplication(ApplicationBootstrapConfig $config): Application
    {
        return new Application(
            root: $config->root,
            sessionRuntime: $config->sessionRuntime ?? new NativeSessionRuntime(),
            middlewarePipeline: $config->middlewarePipeline ?? new MiddlewarePipeline(),
            exceptionHandler: $config->exceptionHandler ?? new RethrowingExceptionHandler(),
        );
    }

    private static function loadEnvironment(ApplicationBootstrapConfig $config): void
    {
        if (!$config->loadDotenv || !is_file($config->root . '/.env')) {
            return;
        }

        Dotenv::createImmutable($config->root)->safeLoad();
    }
}
