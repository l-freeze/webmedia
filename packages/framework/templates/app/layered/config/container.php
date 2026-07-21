<?php

declare(strict_types=1);

use App\{{ namespace }}\Domain\Repository\IHealthCheckRepository;
use App\{{ namespace }}\Infrastructure\Database\ConnectionFactory;
use App\{{ namespace }}\Infrastructure\Http\Middleware\AppNameHeaderMiddleware;
use App\{{ namespace }}\Infrastructure\Persistence\PdoHealthCheckRepository;
use Pera\Framework\Container\Container;

$root = dirname(__DIR__, 3);
$container = new Container();

$sharedContainer = require $root . '/apps/shared/config/container.php';
if (!is_callable($sharedContainer)) {
    throw new \RuntimeException('Shared container configuration is invalid.');
}
$sharedContainer($container, $root);

$container->singleton(id: \PDO::class, factory: static fn (): \PDO => (new ConnectionFactory(
    dsn: 'sqlite:' . $root . '/apps/{{ name }}/storage/app.sqlite',
))->create());
$container->bind(abstract: IHealthCheckRepository::class, concrete: PdoHealthCheckRepository::class);
$container->singleton(id: AppNameHeaderMiddleware::class, factory: static fn (): AppNameHeaderMiddleware => new AppNameHeaderMiddleware(appName: '{{ name }}'));

return $container;
