<?php

declare(strict_types=1);

namespace Pera\Framework\Runtime;

use Pera\Framework\Application\IApplication;
use Closure;

final readonly class FrankenWorkerRuntime implements IRuntime
{
    public function __construct(private Closure $frankenHandleRequest)
    {
    }

    public function run(IApplication $application): void
    {
        while (($this->frankenHandleRequest)(static function () use ($application): void {
            $application->handleGlobals();
        })) {
        }
    }
}
