<?php

declare(strict_types=1);

namespace Pera\Framework\Http\ExceptionHandling;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Throwable;

final readonly class PlainTextExceptionHandler implements IExceptionHandler
{
    public function __construct(
        private string $message = 'Internal Server Error',
    ) {
    }

    public function handle(Throwable $throwable, Request $request): Response
    {
        return new Response(
            body: $this->message,
            status: Response::HTTP_INTERNAL_SERVER_ERROR,
            headers: ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }
}
