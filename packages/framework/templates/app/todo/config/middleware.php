<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$sharedMiddleware = require $root . '/apps/shared/config/middleware.php';
if (!is_array($sharedMiddleware)) {
    throw new \RuntimeException('Shared middleware configuration is invalid.');
}

return [
    ...$sharedMiddleware,

    // Shared middleware defaults to security headers and CSRF protection.
    // Its config/middleware.php explains how to split CSRF per app when needed.
    // Add app-specific middleware here.
    // Example: App\YourApp\Infrastructure\Http\Middleware\YourMiddleware::class,
];
