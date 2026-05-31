<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Routing\Router;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$server = Request::stringMap(values: $_SERVER);
$requestUri = $server['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$path = is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : '/';
$request = new Request(
    method: strtoupper($server['REQUEST_METHOD'] ?? 'GET'),
    path: $path,
    query: Request::stringMap(values: $_GET),
    body: Request::mixedMap(values: $_POST),
    server: $server,
    files: Request::mixedMap(values: $_FILES),
);
$sessionRuntime = new NativeSessionRuntime();
$sessionRuntime->start(secure: $request->isHttps());
/** @var array<string, mixed> $_SESSION */
$session = new Session(state: new SessionState(data: $_SESSION), runtime: $sessionRuntime);

$isCms = str_starts_with($request->path, '/admin');
$container = require $root . ($isCms ? '/apps/cms/config/container.php' : '/apps/front/config/container.php');
$routes = require $root . ($isCms ? '/apps/cms/config/routes.php' : '/apps/front/config/routes.php');
if (!$container instanceof Container || !is_array($routes)) {
    throw new RuntimeException('Application configuration is invalid.');
}
/** @var array<int, array{string, string, array{class-string, string}|class-string}> $routes */

$container->instance(Request::class, $request);
$container->instance(Session::class, $session);

$router = new Router(container: $container, routes: $routes);
$router->dispatch(request: $request)->send();
