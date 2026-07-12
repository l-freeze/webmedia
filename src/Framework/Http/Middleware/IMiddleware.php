<?php

declare(strict_types=1);

namespace App\Framework\Http\Middleware;

use App\Framework\Http\Request;
use App\Framework\Http\Response;

interface IMiddleware
{
    public function process(Request $request, IRequestHandler $handler): Response;
}
