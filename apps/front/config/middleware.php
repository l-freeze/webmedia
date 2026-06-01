<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Http\Middleware\CorsMiddleware;

$root = dirname(__DIR__, 3);
$sharedMiddleware = require $root . '/apps/shared/config/middleware.php';
if (!is_array($sharedMiddleware)) {
    throw new \RuntimeException('Shared middleware configuration is invalid.');
}

return [
    ...$sharedMiddleware,
    CorsMiddleware::class,
];
