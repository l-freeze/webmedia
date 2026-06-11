<?php

declare(strict_types=1);

namespace Tests\Scaffold;

use PHPUnit\Framework\TestCase;

final class ScaffoldAppTest extends TestCase
{
    public function testBareTemplateGeneratesConfigOnlyStarter(): void
    {
        $root = $this->temporaryRoot();

        try {
            $output = $this->runScaffold(root: $root, name: 'api', pathPrefix: '/api', template: 'bare');

            self::assertStringContainsString('Created app scaffold: apps/api (bare)', $output);
            self::assertStringContainsString('Bare starter:', $output);
            self::assertFileExists($root . '/apps/api/config/routes.php');
            self::assertFileExists($root . '/apps/api/config/container.php');
            self::assertFileExists($root . '/apps/api/config/middleware.php');
            self::assertFileExists($root . '/apps/api/src/.gitkeep');
            self::assertFileDoesNotExist($root . '/apps/api/templates/index.latte');
            self::assertFileDoesNotExist($root . '/apps/api/src/Infrastructure/Http/Controller/Index/Controller.php');

            $routes = $this->read(path: $root . '/apps/api/config/routes.php');
            self::assertStringContainsString('return [', $routes);
            self::assertStringNotContainsString('IndexController::class', $routes);

            $container = $this->read(path: $root . '/apps/api/config/container.php');
            self::assertStringContainsString("require \$root . '/apps/shared/config/container.php'", $container);
            self::assertStringNotContainsString("require \$root . '/apps/kernel/config/container.php'", $container);
            self::assertStringNotContainsString('TemplateRenderer::class', $container);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testMinimalTemplateGeneratesSingleControllerHtmlStarter(): void
    {
        $root = $this->temporaryRoot();

        try {
            $output = $this->runScaffold(root: $root, name: 'mini-app', pathPrefix: '/mini/', template: 'minimal');

            self::assertStringContainsString('Created app scaffold: apps/mini-app (minimal)', $output);
            self::assertStringContainsString('Minimal starter:', $output);
            self::assertStringContainsString('Single-controller HTML starter at /mini.', $output);
            self::assertFileExists($root . '/apps/mini-app/src/Infrastructure/Http/Controller/Index/Controller.php');
            self::assertFileExists($root . '/apps/mini-app/templates/index.latte');

            $routes = $this->read(path: $root . '/apps/mini-app/config/routes.php');
            self::assertStringContainsString("['GET', '/mini', IndexController::class]", $routes);
            self::assertStringNotContainsString("'/mini/'", $routes);

            $container = $this->read(path: $root . '/apps/mini-app/config/container.php');
            self::assertStringContainsString("require \$root . '/apps/kernel/config/container.php'", $container);
            self::assertStringContainsString("require \$root . '/apps/shared/config/container.php'", $container);
            self::assertStringContainsString('TemplateRenderer::class', $container);
            self::assertStringContainsString('LatteTemplateRenderer', $container);
            self::assertStringNotContainsString('sqlite::memory:', $container);
            self::assertStringNotContainsString('PDO::class', $container);

            $controller = $this->read(path: $root . '/apps/mini-app/src/Infrastructure/Http/Controller/Index/Controller.php');
            self::assertStringContainsString('private PDO $pdo', $controller);
            self::assertStringContainsString('private TemplateRenderer $renderer', $controller);
            self::assertStringContainsString('CREATE TABLE IF NOT EXISTS scaffold_messages', $controller);
            self::assertStringContainsString('INSERT INTO scaffold_messages', $controller);
            self::assertStringContainsString("template: 'index.latte'", $controller);

            $template = $this->read(path: $root . '/apps/mini-app/templates/index.latte');
            self::assertStringContainsString('<h1>{$title}</h1>', $template);
            self::assertStringContainsString('Route: <code>{$path}</code>', $template);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testMinimalTemplateHandlesRootPathPrefix(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->runScaffold(root: $root, name: 'root-app', pathPrefix: '/', template: 'minimal');

            $routes = $this->read(path: $root . '/apps/root-app/config/routes.php');
            self::assertStringContainsString("['GET', '/', IndexController::class]", $routes);
            self::assertStringNotContainsString("'//", $routes);

            $controller = $this->read(path: $root . '/apps/root-app/src/Infrastructure/Http/Controller/Index/Controller.php');
            self::assertStringContainsString("'path' => '/'", $controller);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testTodoTemplateGeneratesSmallPdoStarter(): void
    {
        $root = $this->temporaryRoot();

        try {
            $output = $this->runScaffold(root: $root, name: 'todo', pathPrefix: '/todo', template: 'todo');

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
            $this->runScaffold(root: $root, name: 'root-todo', pathPrefix: '/', template: 'todo');

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

    private function runScaffold(string $root, string $name, string $pathPrefix, string $template): string
    {
        $projectRoot = dirname(__DIR__, 2);
        $command = implode(' ', [
            escapeshellarg(PHP_BINARY),
            escapeshellarg($projectRoot . '/bin/scaffold-app'),
            escapeshellarg($name),
            escapeshellarg($pathPrefix),
            '--template=' . escapeshellarg($template),
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
