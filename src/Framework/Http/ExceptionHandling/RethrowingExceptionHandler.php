<?php

declare(strict_types=1);

namespace App\Framework\Http\ExceptionHandling;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use Throwable;

final readonly class RethrowingExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(Throwable $throwable, Request $request): Response
    {
        throw $throwable;
    }
}
