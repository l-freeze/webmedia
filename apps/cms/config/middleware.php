<?php

declare(strict_types=1);

use App\Cms\Presentation\Http\Middleware\CmsAuditMiddleware;
use App\Cms\Presentation\Http\Middleware\RequireCmsPermissionMiddleware;
use App\Cms\Presentation\Http\Middleware\RequireCmsSessionMiddleware;

$root = dirname(__DIR__, 3);
$sharedMiddleware = require $root . '/apps/shared/config/middleware.php';
if (!is_array($sharedMiddleware)) {
    throw new \RuntimeException('Shared middleware configuration is invalid.');
}

return [
    ...$sharedMiddleware,
    RequireCmsSessionMiddleware::class,
    RequireCmsPermissionMiddleware::class,
    CmsAuditMiddleware::class,
];
