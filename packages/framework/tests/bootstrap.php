<?php

declare(strict_types=1);

$autoloadPaths = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php',
];
foreach ($autoloadPaths as $autoloadPath) {
    if (is_file($autoloadPath)) {
        require $autoloadPath;
        spl_autoload_register(static function (string $class): void {
            $prefix = 'Pera\\Framework\\Tests\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relativeClass = substr($class, strlen($prefix));
            $path = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (is_file($path)) {
                require $path;
            }
        });

        return;
    }
}

throw new RuntimeException('Composer autoload file was not found. Run composer install first.');
