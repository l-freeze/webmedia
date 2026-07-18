<?php

declare(strict_types=1);

namespace Pera\Framework\Application;

interface IApplication
{
    public function handleGlobals(): void;
}
