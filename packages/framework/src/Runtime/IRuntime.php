<?php

declare(strict_types=1);

namespace Pera\Framework\Runtime;

use Pera\Framework\Application\IApplication;

interface IRuntime
{
    public function run(IApplication $application): void;
}
