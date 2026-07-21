<?php

declare(strict_types=1);

use App\{{ namespace }}\Infrastructure\Http\Middleware\AppNameHeaderMiddleware;

$root = dirname(__DIR__, 3);
$sharedMiddleware = require $root . '/apps/shared/config/middleware.php';
if (!is_array($sharedMiddleware)) {
    throw new \RuntimeException('Shared middleware configuration is invalid.');
}

return [
    ...$sharedMiddleware,

    // Shared middleware includes security headers and CSRF protection.
    // CSRF is disabled when APP_ENV is stg, staging, dev, develop, development, or test.
    // Exclude paths with CSRF_EXCLUDED_PATHS=/webhook/*,/api/external.
    // Add app-specific middleware here.
    // Example: AppNameHeaderMiddleware::class,
    AppNameHeaderMiddleware::class,
];
