<?php

declare(strict_types=1);

use App\Shared\Security\Infrastructure\Http\SecurityHeadersMiddleware;
use Pera\Framework\Security\CsrfMiddleware;

return [
    SecurityHeadersMiddleware::class,

    // CSRF is the safe default for browser/session applications.
    // If this project later adds a stateless API, comment out or remove this
    // shared entry and add CsrfMiddleware::class to each browser/session app's
    // config/middleware.php instead.
    CsrfMiddleware::class,
];
