<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use App\Framework\Application\Application;

final class ClassicRuntime implements Runtime
{
    public function run(Application $application): void
    {
        $application->handleGlobals();
    }
}
