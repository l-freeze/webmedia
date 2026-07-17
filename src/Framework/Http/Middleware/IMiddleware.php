<?php

declare(strict_types=1);

namespace Pera\Framework\Http\Middleware;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

interface IMiddleware
{
    public function process(Request $request, IRequestHandler $handler): Response;
}
