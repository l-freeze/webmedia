<?php

declare(strict_types=1);

namespace App\Framework\Bootstrap;

use App\Framework\Application\IApplication;
use App\Framework\Http\ExceptionHandling\IExceptionHandler;
use App\Framework\Http\Middleware\MiddlewarePipeline;
use App\Framework\Security\NativeSessionRuntime;
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
