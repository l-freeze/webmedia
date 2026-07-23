<?php

declare(strict_types=1);

use Pera\Framework\Bootstrap\ApplicationBootstrap;
use Pera\Framework\Bootstrap\ApplicationBootstrapConfig;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

$applicationFactory = require $root . '/apps/kernel/config/application.php';
if (!$applicationFactory instanceof Closure) {
    throw new RuntimeException('Kernel application factory configuration is invalid.');
}

ApplicationBootstrap::run(
    config: new ApplicationBootstrapConfig(root: $root, applicationFactory: $applicationFactory),
);
