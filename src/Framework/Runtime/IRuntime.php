<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use App\Framework\Application\IApplication;

interface IRuntime
{
    public function run(IApplication $application): void;
}
