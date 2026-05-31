<?php

declare(strict_types=1);

use App\Framework\Application\Application;
use App\Framework\Runtime\RuntimeFactory;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$application = new Application(root: $root);
$runtime = RuntimeFactory::fromEnvironment();
$runtime->run(handleRequest: static function () use ($application): void {
    $application->handleGlobals();
});
