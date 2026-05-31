<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$sharedMiddleware = require $root . '/apps/shared/config/middleware.php';

return [
    ...$sharedMiddleware,
];
