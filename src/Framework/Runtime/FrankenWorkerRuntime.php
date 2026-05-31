<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use Closure;

final readonly class FrankenWorkerRuntime implements Runtime
{
    public function __construct(private Closure $frankenHandleRequest)
    {
    }

    public function run(callable $handleRequest): void
    {
        while (($this->frankenHandleRequest)($handleRequest)) {
        }
    }
}
