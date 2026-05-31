<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use Closure;

final readonly class RuntimeFactory
{
    private const FRANKEN_WORKER = 'frankenphp-worker';

    private const ROADRUNNER_WORKER = 'roadrunner-worker';

    private function __construct()
    {
    }

    public static function fromEnvironment(): Runtime
    {
        $runtime = $_ENV['APP_RUNTIME'] ?? getenv('APP_RUNTIME') ?: 'classic';

        return self::fromRuntime(
            runtime: is_string($runtime) ? $runtime : 'classic',
            frankenAvailable: function_exists('frankenphp_handle_request'),
            roadRunnerAvailable: class_exists('Spiral\\RoadRunner\\Http\\PSR7Worker')
                && class_exists('Nyholm\\Psr7\\Factory\\Psr17Factory'),
        );
    }

    /**
     * @param callable(callable(): void): bool|null $frankenHandleRequest
     */
    public static function fromRuntime(
        string $runtime,
        bool $frankenAvailable,
        bool $roadRunnerAvailable = false,
        ?callable $frankenHandleRequest = null,
        ?Runtime $roadRunnerRuntime = null,
    ): Runtime {
        if ($runtime === self::FRANKEN_WORKER && $frankenAvailable) {
            return new FrankenWorkerRuntime(
                frankenHandleRequest: $frankenHandleRequest !== null
                    ? Closure::fromCallable($frankenHandleRequest)
                    : Closure::fromCallable('frankenphp_handle_request'),
            );
        }

        if ($runtime === self::ROADRUNNER_WORKER && $roadRunnerAvailable) {
            return $roadRunnerRuntime ?? RoadRunnerRuntime::create();
        }

        return new ClassicRuntime();
    }
}
