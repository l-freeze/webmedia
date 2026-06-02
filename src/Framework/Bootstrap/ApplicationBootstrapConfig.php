<?php

declare(strict_types=1);

namespace App\Framework\Bootstrap;

use App\Framework\Application\ApplicationInterface;
use App\Framework\Http\ExceptionHandling\ExceptionHandlerInterface;
use App\Framework\Http\Middleware\MiddlewarePipeline;
use App\Framework\Security\NativeSessionRuntime;
use Closure;

final readonly class ApplicationBootstrapConfig
{
    public function __construct(
        public string $root,
        public ?NativeSessionRuntime $sessionRuntime = null,
        public ?MiddlewarePipeline $middlewarePipeline = null,
        public ?ExceptionHandlerInterface $exceptionHandler = null,
        /** @var Closure(self): ApplicationInterface|null */
        public ?Closure $applicationFactory = null,
    ) {
    }
}
