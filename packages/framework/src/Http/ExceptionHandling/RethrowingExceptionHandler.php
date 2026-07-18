<?php

declare(strict_types=1);

namespace Pera\Framework\Http\ExceptionHandling;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Throwable;

final readonly class RethrowingExceptionHandler implements IExceptionHandler
{
    public function handle(Throwable $throwable, Request $request): Response
    {
        throw $throwable;
    }
}
