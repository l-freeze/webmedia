<?php

declare(strict_types=1);

namespace App\Framework\Http\Middleware;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use Closure;

final readonly class CallableRequestHandler implements IRequestHandler
{
    /** @param Closure(Request): Response $handler */
    public function __construct(
        private Closure $handler,
    ) {
    }

    public function handle(Request $request): Response
    {
        return ($this->handler)($request);
    }
}
