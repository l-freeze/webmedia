<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

final class ClassicRuntime implements Runtime
{
    public function run(callable $handleRequest): void
    {
        $handleRequest();
    }
}
