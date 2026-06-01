<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Middleware;

use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class RequireCmsSessionMiddleware implements MiddlewareInterface
{
    /**
     * @param array<int, string> $publicPaths
     */
    public function __construct(
        private Session $session,
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

        if (!$this->session->has(key: 'cms.user_id')) {
            return Response::redirect(location: '/admin/login');
        }

        return $handler->handle(request: $request);
    }
}
