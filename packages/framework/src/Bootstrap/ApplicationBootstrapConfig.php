<?php

declare(strict_types=1);

namespace Pera\Framework\Bootstrap;

use Pera\Framework\Application\IApplication;
use Pera\Framework\Http\ExceptionHandling\IExceptionHandler;
use Pera\Framework\Http\Middleware\MiddlewarePipeline;
use Pera\Framework\Security\NativeSessionRuntime;
use Closure;

final readonly class ApplicationBootstrapConfig
{
    public function __construct(
        public string $root,
        public ?NativeSessionRuntime $sessionRuntime = null,
        public ?MiddlewarePipeline $middlewarePipeline = null,
        public ?IExceptionHandler $exceptionHandler = null,
        /** @var Closure(self): IApplication|null */
        public ?Closure $applicationFactory = null,
    ) {
    }
}
