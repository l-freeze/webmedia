<?php

declare(strict_types=1);

namespace App\Framework\Http\Middleware;

use App\Framework\Http\Request;
use App\Framework\Http\Response;

interface RequestHandlerInterface
{
    public function handle(Request $request): Response;
}
