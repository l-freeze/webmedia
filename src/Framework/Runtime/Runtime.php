<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use App\Framework\Application\Application;

interface Runtime
{
    public function run(Application $application): void;
}
