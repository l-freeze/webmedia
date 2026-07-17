<?php

declare(strict_types=1);

namespace Pera\Framework\Http\Middleware;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

interface IRequestHandler
{
    public function handle(Request $request): Response;
}
