<?php

declare(strict_types=1);

namespace App\Framework\Http\ExceptionHandling;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use Throwable;

interface ExceptionHandlerInterface
{
    public function handle(Throwable $throwable, Request $request): Response;
}
