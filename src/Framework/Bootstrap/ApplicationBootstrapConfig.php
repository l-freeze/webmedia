<?php

declare(strict_types=1);

namespace App\Framework\Bootstrap;

use App\Framework\Http\ExceptionHandling\ExceptionHandlerInterface;
use App\Framework\Http\Middleware\MiddlewarePipeline;
use App\Framework\Security\NativeSessionRuntime;

final readonly class ApplicationBootstrapConfig
{
    public function __construct(
        public string $root,
        public bool $loadDotenv = true,
        public ?NativeSessionRuntime $sessionRuntime = null,
        public ?MiddlewarePipeline $middlewarePipeline = null,
        public ?ExceptionHandlerInterface $exceptionHandler = null,
    ) {
    }
}
