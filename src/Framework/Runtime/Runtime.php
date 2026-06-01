<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use App\Framework\Application\ApplicationInterface;

interface Runtime
{
    public function run(ApplicationInterface $application): void;
}
