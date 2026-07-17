<?php

declare(strict_types=1);

use Pera\Framework\Container\Container;
use App\Kernel\Database\Infrastructure\ConnectionFactory;

return static function (Container $container): void {
    $container->workerSingleton(id: \PDO::class, factory: fn (): \PDO => (new ConnectionFactory())->create());
};
