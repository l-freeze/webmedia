<?php

declare(strict_types=1);

use Pera\Framework\Container\Container;
use Pera\Framework\Logging\PlaceholderLogger;
use Psr\Log\LoggerInterface;

return static function (Container $container, string $root): void {
    $container->singleton(
        id: LoggerInterface::class,
        factory: static fn (): LoggerInterface => new PlaceholderLogger(),
    );
};
