<?php

declare(strict_types=1);

use App\Framework\Http\Request;
use App\Framework\Routing\Router;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$request = Request::fromGlobals();
$isCms = str_starts_with($request->path, '/admin');
$container = require $root . ($isCms ? '/apps/cms/config/container.php' : '/apps/front/config/container.php');
$routes = require $root . ($isCms ? '/apps/cms/config/routes.php' : '/apps/front/config/routes.php');

$router = new Router($container, $routes);
$router->dispatch($request)->send();
