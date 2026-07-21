<?php

declare(strict_types=1);

use App\{{ namespace }}\Application\Todo\ITodoRepository;
use App\{{ namespace }}\Infrastructure\Database\ConnectionFactory;
use App\{{ namespace }}\Infrastructure\Persistence\PdoTodoRepository;
use Pera\Framework\Container\Container;
use Pera\Framework\View\ITemplateRenderer;
use App\Shared\View\Infrastructure\LatteTemplateRenderer;

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
$container->bind(abstract: ITodoRepository::class, concrete: PdoTodoRepository::class);
$container->singleton(id: ITemplateRenderer::class, factory: fn (): ITemplateRenderer => new LatteTemplateRenderer(
    templateDirectory: $root . '/apps/{{ name }}/templates',
));

return $container;
