<?php

declare(strict_types=1);

use App\Shared\Security\Infrastructure\Http\SecurityHeadersMiddleware;
use Pera\Framework\Security\CsrfMiddleware;

return [
    SecurityHeadersMiddleware::class,
    CsrfMiddleware::class,
];
