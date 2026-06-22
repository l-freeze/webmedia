<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Middleware;

use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;

final readonly class RequireCmsSessionMiddleware implements MiddlewareInterface
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

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (in_array($request->path, $this->publicPaths, true)) {
            return $handler->handle(request: $request);
        }

        if (!$this->guard->isAuthenticated()) {
            return Response::redirect(location: '/admin/login');
        }

        return $handler->handle(request: $request);
    }
}
