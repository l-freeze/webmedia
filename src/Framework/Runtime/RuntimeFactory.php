<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use Closure;

final readonly class RuntimeFactory
{
    private const FRANKEN_WORKER = 'frankenphp-worker';

    private function __construct()
    {
    }

    public static function fromEnvironment(): Runtime
    {
        $runtime = $_ENV['APP_RUNTIME'] ?? $_SERVER['APP_RUNTIME'] ?? getenv('APP_RUNTIME') ?: 'classic';

        return self::fromRuntime(
            runtime: is_string($runtime) ? $runtime : 'classic',
            frankenAvailable: function_exists('frankenphp_handle_request'),
        );
    }

    /**
     * @param callable(callable(): void): bool|null $frankenHandleRequest
     */
    public static function fromRuntime(
        string $runtime,
        bool $frankenAvailable,
        ?callable $frankenHandleRequest = null,
    ): Runtime {
        if ($runtime === self::FRANKEN_WORKER && $frankenAvailable) {
            return new FrankenWorkerRuntime(
                frankenHandleRequest: $frankenHandleRequest !== null
                    ? Closure::fromCallable($frankenHandleRequest)
                    : Closure::fromCallable('frankenphp_handle_request'),
            );
        }

        return new ClassicRuntime();
    }
}
