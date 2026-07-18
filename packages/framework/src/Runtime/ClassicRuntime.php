<?php

declare(strict_types=1);

namespace Pera\Framework\Runtime;

use Pera\Framework\Application\IApplication;

final class ClassicRuntime implements IRuntime
{
    public function run(IApplication $application): void
    {
        $application->handleGlobals();
    }
}
