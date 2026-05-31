<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use App\Framework\Application\Application;
use Closure;

final readonly class FrankenWorkerRuntime implements Runtime
{
    public function __construct(private Closure $frankenHandleRequest)
    {
    }

    public function run(Application $application): void
    {
        while (($this->frankenHandleRequest)(static function () use ($application): void {
            $application->handleGlobals();
        })) {
        }
    }
}
