<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use App\Framework\Application\IApplication;

final class ClassicRuntime implements IRuntime
{
    public function run(IApplication $application): void
    {
        $application->handleGlobals();
    }
}
