<?php

declare(strict_types=1);

$_ENV['APP_RUNTIME'] = 'frankenphp-worker';
putenv('APP_RUNTIME=frankenphp-worker');

require dirname(__DIR__) . '/public/index.php';
