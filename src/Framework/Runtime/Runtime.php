<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

interface Runtime
{
    /** @param callable(): void $handleRequest */
    public function run(callable $handleRequest): void;
}
