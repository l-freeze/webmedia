<?php

declare(strict_types=1);

namespace App\Framework\Application;

interface IApplication
{
    public function handleGlobals(): void;
}
