<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Infrastructure\Http\Middleware;

use Pera\Framework\Http\Middleware\IMiddleware;
use Pera\Framework\Http\Middleware\IRequestHandler;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

final readonly class AppNameHeaderMiddleware implements IMiddleware
{
    public function __construct(private string $appName)
    {
    }

    public function process(Request $request, IRequestHandler $handler): Response
    {
        return $handler->handle(request: $request)->withHeader(name: 'X-App-Name', value: $this->appName);
    }
}
