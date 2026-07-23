<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Middleware;

use App\Cms\Infrastructure\Security\CmsGuard;
use Pera\Framework\Http\Middleware\IMiddleware;
use Pera\Framework\Http\Middleware\IRequestHandler;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

final readonly class RequireCmsSessionMiddleware implements IMiddleware
{
    /**
     * @param array<int, string> $publicPaths
     */
    public function __construct(
        private CmsGuard $guard,
        private array $publicPaths = [
            '/admin/login',
        ],
    ) {
    }

    public function process(Request $request, IRequestHandler $handler): Response
    {
        if (in_array($request->path, $this->publicPaths, true)) {
            return $handler->handle(request: $request);
        }

        if (!$this->guard->isAuthenticated()) {
            return Response::redirect('/admin/login');
        }

        return $handler->handle(request: $request);
    }
}
