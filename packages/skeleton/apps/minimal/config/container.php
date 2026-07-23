<?php

declare(strict_types=1);

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

$container->singleton(id: ITemplateRenderer::class, factory: fn (): ITemplateRenderer => new LatteTemplateRenderer(
    templateDirectory: $root . '/apps/minimal/templates',
));

return $container;
