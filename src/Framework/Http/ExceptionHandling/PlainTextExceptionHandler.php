<?php

declare(strict_types=1);

namespace App\Framework\Http\ExceptionHandling;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use Throwable;

final readonly class PlainTextExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct(
        private string $message = 'Internal Server Error',
    ) {
    }

    public function handle(Throwable $throwable, Request $request): Response
    {
        return new Response(
            body: $this->message,
            status: 500,
            headers: ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }
}
