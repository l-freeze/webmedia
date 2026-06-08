<?php

declare(strict_types=1);

namespace Tests\Scaffold;

use PHPUnit\Framework\TestCase;

final class ScaffoldAppTest extends TestCase
{
    public function testTodoTemplateGeneratesSmallPdoStarter(): void
    {
        $root = $this->temporaryRoot();

        try {
            $output = $this->runScaffold(root: $root, name: 'todo', pathPrefix: '/todo');

            self::assertStringContainsString('Created app scaffold: apps/todo (todo)', $output);
            self::assertStringContainsString('Todo starter:', $output);
            self::assertFileExists($root . '/apps/todo/src/Application/Todo/TodoRepository.php');
            self::assertFileExists($root . '/apps/todo/src/Infrastructure/Persistence/PdoTodoRepository.php');
            self::assertFileExists($root . '/apps/todo/templates/index.latte');

            $routes = $this->read(path: $root . '/apps/todo/config/routes.php');
            self::assertStringContainsString("['GET', '/todo', TodoIndexController::class]", $routes);
            self::assertStringContainsString("['POST', '/todo/{id:[a-f0-9]+}/complete', TodoCompleteController::class]", $routes);

            $container = $this->read(path: $root . '/apps/todo/config/container.php');
            self::assertStringContainsString("require \$root . '/apps/kernel/config/container.php'", $container);
            self::assertStringContainsString('TodoRepository::class', $container);
            self::assertStringContainsString('LatteTemplateRenderer', $container);

            $repository = $this->read(path: $root . '/apps/todo/src/Infrastructure/Persistence/PdoTodoRepository.php');
            self::assertStringContainsString('CREATE TABLE IF NOT EXISTS todos', $repository);
            self::assertStringContainsString('INSERT INTO todos', $repository);

            $indexController = $this->read(path: $root . '/apps/todo/src/Infrastructure/Http/Controller/Todo/Index/Controller.php');
            self::assertStringNotContainsString('SELECT ', $indexController);
            self::assertStringNotContainsString('INSERT ', $indexController);
            self::assertStringNotContainsString('CREATE TABLE', $indexController);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testTodoTemplateHandlesRootPathPrefix(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->runScaffold(root: $root, name: 'root-todo', pathPrefix: '/');

            $routes = $this->read(path: $root . '/apps/root-todo/config/routes.php');
            self::assertStringContainsString("['GET', '/', TodoIndexController::class]", $routes);
            self::assertStringContainsString("['POST', '/{id:[a-f0-9]+}/complete', TodoCompleteController::class]", $routes);
            self::assertStringNotContainsString("'//", $routes);

            $indexController = $this->read(path: $root . '/apps/root-todo/src/Infrastructure/Http/Controller/Todo/Index/Controller.php');
            self::assertStringContainsString("'basePath' => ''", $indexController);

            $storeController = $this->read(path: $root . '/apps/root-todo/src/Infrastructure/Http/Controller/Todo/Store/Controller.php');
            self::assertStringContainsString("Response::redirect(location: '/')", $storeController);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    private function temporaryRoot(): string
    {
        return sys_get_temp_dir() . '/webmedia-scaffold-app-test-' . bin2hex(random_bytes(4));
    }

    private function runScaffold(string $root, string $name, string $pathPrefix): string
    {
        $projectRoot = dirname(__DIR__, 2);
        $command = implode(' ', [
            escapeshellarg(PHP_BINARY),
            escapeshellarg($projectRoot . '/bin/scaffold-app'),
            escapeshellarg($name),
            escapeshellarg($pathPrefix),
            '--template=todo',
            '--root=' . escapeshellarg($root),
            '2>&1',
        ]);

        $lines = [];
        exec($command, $lines, $status);
        $output = implode("\n", $lines);

        self::assertSame(0, $status, $output);

        return $output;
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        self::assertIsString($contents);

        return $contents;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        self::assertIsArray($items);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $child = $path . '/' . $item;
            if (is_dir($child)) {
                $this->removeDirectory(path: $child);
                continue;
            }
            unlink($child);
        }

        rmdir($path);
    }
}
