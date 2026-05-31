<?php

declare(strict_types=1);

namespace App\Framework\Http\Middleware;

use App\Framework\Http\Request;
use App\Framework\Http\Response;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response;
}
