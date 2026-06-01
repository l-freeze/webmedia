<?php

declare(strict_types=1);

namespace App\Framework\Application;

use App\Framework\Http\Request;
use App\Framework\Http\Response;

interface ApplicationInterface
{
    public function handleGlobals(): void;

    /** @param array<string, string> $cookies */
    public function handleWorkerRequest(Request $request, array $cookies): Response;
}
