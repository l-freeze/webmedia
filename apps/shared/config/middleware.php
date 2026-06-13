<?php

declare(strict_types=1);

use App\Shared\Security\Infrastructure\Http\SecurityHeadersMiddleware;
use App\Framework\Security\CsrfMiddleware;

return [
    SecurityHeadersMiddleware::class,
    CsrfMiddleware::class,
];
