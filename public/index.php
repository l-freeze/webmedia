<?php

declare(strict_types=1);

use App\Framework\Bootstrap\ApplicationBootstrap;
use App\Framework\Bootstrap\ApplicationBootstrapConfig;

require dirname(__DIR__) . '/vendor/autoload.php';

ApplicationBootstrap::run(
    config: new ApplicationBootstrapConfig(root: dirname(__DIR__)),
);
