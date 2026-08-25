<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Scaffold;

use Pera\Framework\Container\Container;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

final class ScaffoldAppTest extends TestCase
{
    public function testBareTemplateGeneratesRunnablePlainTextStarter(): void
    {
        $root = $this->temporaryRoot();

        try {
            $output = $this->runScaffold(root: $root, name: 'api', pathPrefix: '/api', template: 'bare');

            self::assertStringContainsString('Created app scaffold: apps/api (bare)', $output);
            self::assertStringContainsString('Bare starter:', $output);
            self::assertStringContainsString('Registered application in apps/kernel/config/application.php: api: /api', $output);
            self::assertFileExists($root . '/apps/api/config/routes.php');
            self::assertFileExists($root . '/apps/api/config/container.php');
            self::assertFileExists($root . '/apps/api/config/middleware.php');
            self::assertFileExists($root . '/apps/api/src/Presentation/Http/Controller/Index/Controller.php');
            self::assertFileDoesNotExist($root . '/apps/api/templates/index.latte');
            self::assertFileDoesNotExist($root . '/apps/api/src/.gitkeep');

            $routes = $this->read(path: $root . '/apps/api/config/routes.php');
            self::assertStringContainsString("['GET', '/api', IndexController::class]", $routes);

            $container = $this->read(path: $root . '/apps/api/config/container.php');
            self::assertStringNotContainsString("require \$root . '/apps/shared/config/container.php'", $container);
            self::assertStringNotContainsString("require \$root . '/apps/kernel/config/container.php'", $container);
            self::assertStringNotContainsString('ITemplateRenderer::class', $container);

            $middleware = $this->read(path: $root . '/apps/api/config/middleware.php');
            self::assertStringNotContainsString('apps/shared/config/middleware.php', $middleware);
            self::assertStringContainsString('Add app-specific middleware here.', $middleware);
            self::assertStringContainsString('YourMiddleware::class', $middleware);

            $controllerPath = $root . '/apps/api/src/Presentation/Http/Controller/Index/Controller.php';
            $controller = $this->read(path: $controllerPath);
            self::assertStringContainsString('use Pera\\Framework\\Http\\Response;', $controller);
            self::assertStringContainsString("body: 'Hello from api.'", $controller);
            self::assertStringContainsString("'Content-Type' => 'text/plain; charset=utf-8'", $controller);
            self::assertStringNotContainsString('ITemplateRenderer', $controller);
            self::assertStringNotContainsString('Session', $controller);
            self::assertStringNotContainsString('PDO', $controller);

            require_once $controllerPath;
            $routeDefinitions = require $root . '/apps/api/config/routes.php';
            self::assertIsArray($routeDefinitions);
            /** @var array<int, array{0:string, 1:string, 2:class-string|array{0:class-string, 1:string}, 3?:array<string, mixed>}> $routeDefinitions */
            $appContainer = require $root . '/apps/api/config/container.php';
            self::assertInstanceOf(Container::class, $appContainer);
            $response = (new Router(routes: $routeDefinitions))->dispatch(
                request: new Request(method: 'GET', path: '/api', query: [], body: [], server: []),
                container: $appContainer,
            );
            self::assertSame(Response::HTTP_OK, $response->status);
            self::assertSame('Hello from api.', $response->body);
            self::assertSame('text/plain; charset=utf-8', $response->headers['Content-Type'] ?? null);

            self::assertNull($this->composerRuntimeRequirement(root: $root, package: 'latte/latte'));
            self::assertNull($this->composerRuntimeRequirement(root: $root, package: 'ext-pdo'));
            self::assertSame('apps/api/src/', $this->composerPsr4Path(root: $root, namespace: 'App\\Api\\'));
            self::assertStringContainsString("name: 'api'", $this->kernelApplicationConfig(root: $root));
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testBareTemplateHandlesRootPathPrefix(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->runScaffold(root: $root, name: 'root-bare', pathPrefix: '/', template: 'bare');

            $routes = $this->read(path: $root . '/apps/root-bare/config/routes.php');
            self::assertStringContainsString("['GET', '/', IndexController::class]", $routes);
            self::assertStringNotContainsString("'//", $routes);

            $controller = $this->read(path: $root . '/apps/root-bare/src/Presentation/Http/Controller/Index/Controller.php');
            self::assertStringContainsString("body: 'Hello from root-bare.'", $controller);
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
            self::assertStringContainsString('Registered application in apps/kernel/config/application.php: mini-app: /mini', $output);
            self::assertFileExists($root . '/apps/mini-app/src/Presentation/Http/Controller/Index/Controller.php');
            self::assertFileExists($root . '/apps/mini-app/templates/index.latte');

            $routes = $this->read(path: $root . '/apps/mini-app/config/routes.php');
            self::assertStringContainsString("['GET', '/mini', IndexController::class]", $routes);
            self::assertStringNotContainsString("'/mini/'", $routes);

            $container = $this->read(path: $root . '/apps/mini-app/config/container.php');
            self::assertStringNotContainsString("require \$root . '/apps/kernel/config/container.php'", $container);
            self::assertStringContainsString("require \$root . '/apps/shared/config/container.php'", $container);
            self::assertStringContainsString('ITemplateRenderer::class', $container);
            self::assertStringContainsString('LatteTemplateRenderer', $container);
            self::assertStringNotContainsString('cacheDirectory:', $container);
            self::assertStringNotContainsString('sqlite::memory:', $container);
            self::assertStringNotContainsString('PDO::class', $container);

            $middleware = $this->read(path: $root . '/apps/mini-app/config/middleware.php');
            self::assertStringContainsString('CSRF protection', $middleware);
            self::assertStringContainsString('split CSRF per app when needed', $middleware);
            self::assertStringNotContainsString('CSRF_EXCLUDED_PATHS', $middleware);
            self::assertStringContainsString('Add app-specific middleware here.', $middleware);
            self::assertStringContainsString('YourMiddleware::class', $middleware);

            $controller = $this->read(path: $root . '/apps/mini-app/src/Presentation/Http/Controller/Index/Controller.php');
            self::assertStringNotContainsString('PDO', $controller);
            self::assertStringContainsString('private ITemplateRenderer $renderer', $controller);
            self::assertStringNotContainsString('CREATE TABLE', $controller);
            self::assertStringNotContainsString('INSERT INTO', $controller);
            self::assertStringContainsString("template: 'index.latte'", $controller);
            self::assertStringNotContainsString('Pera\\Framework\\Security\\Session', $controller);

            $template = $this->read(path: $root . '/apps/mini-app/templates/index.latte');
            self::assertStringContainsString('<h1>{$title}</h1>', $template);
            self::assertStringContainsString('Route: <code>{$path}</code>', $template);

            self::assertSame('^3.0', $this->composerRuntimeRequirement(root: $root, package: 'latte/latte'));
            self::assertNull($this->composerRuntimeRequirement(root: $root, package: 'ext-pdo'));
            self::assertStringContainsString('Updated composer.json runtime requirements:', $output);
            self::assertStringContainsString("composer update\n", $output . "\n");
            self::assertSame('apps/mini-app/src/', $this->composerPsr4Path(root: $root, namespace: 'App\\MiniApp\\'));
            self::assertStringContainsString("pathPrefix: '/mini'", $this->kernelApplicationConfig(root: $root));
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

            $controller = $this->read(path: $root . '/apps/root-app/src/Presentation/Http/Controller/Index/Controller.php');
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
            self::assertStringContainsString('Registered application in apps/kernel/config/application.php: todo: /todo', $output);
            self::assertFileExists($root . '/apps/todo/src/Application/Todo/ITodoRepository.php');
            self::assertFileExists($root . '/apps/todo/src/Infrastructure/Database/ConnectionFactory.php');
            self::assertFileExists($root . '/apps/todo/src/Infrastructure/Persistence/PdoTodoRepository.php');
            self::assertFileExists($root . '/apps/todo/templates/index.latte');

            $routes = $this->read(path: $root . '/apps/todo/config/routes.php');
            self::assertStringContainsString("['GET', '/todo', TodoIndexController::class]", $routes);
            self::assertStringContainsString("['POST', '/todo/{id:[a-f0-9]+}/complete', TodoCompleteController::class]", $routes);

            $container = $this->read(path: $root . '/apps/todo/config/container.php');
            self::assertStringNotContainsString("require \$root . '/apps/kernel/config/container.php'", $container);
            self::assertStringContainsString("dsn: 'sqlite:'", $container);
            self::assertStringContainsString('ConnectionFactory', $container);
            self::assertStringContainsString('ITodoRepository::class', $container);
            self::assertStringContainsString('LatteTemplateRenderer', $container);
            self::assertStringNotContainsString('cacheDirectory:', $container);

            $middleware = $this->read(path: $root . '/apps/todo/config/middleware.php');
            self::assertStringContainsString('CSRF protection', $middleware);
            self::assertStringContainsString('split CSRF per app when needed', $middleware);
            self::assertStringNotContainsString('CSRF_EXCLUDED_PATHS', $middleware);
            self::assertStringContainsString('Add app-specific middleware here.', $middleware);
            self::assertStringContainsString('YourMiddleware::class', $middleware);

            $repository = $this->read(path: $root . '/apps/todo/src/Infrastructure/Persistence/PdoTodoRepository.php');
            self::assertStringContainsString('CREATE TABLE IF NOT EXISTS todos', $repository);
            self::assertStringContainsString('INSERT INTO todos', $repository);

            $indexController = $this->read(path: $root . '/apps/todo/src/Presentation/Http/Controller/Todo/Index/Controller.php');
            self::assertStringNotContainsString('SELECT ', $indexController);
            self::assertStringNotContainsString('INSERT ', $indexController);
            self::assertStringNotContainsString('CREATE TABLE', $indexController);
            self::assertStringContainsString('private Csrf $csrf', $indexController);
            self::assertStringContainsString("'csrfToken' => \$this->csrf->token()", $indexController);

            $template = $this->read(path: $root . '/apps/todo/templates/index.latte');
            self::assertSame(2, substr_count($template, 'name="_csrf" value="{$csrfToken}"'));

            self::assertSame('^3.0', $this->composerRuntimeRequirement(root: $root, package: 'latte/latte'));
            self::assertSame('*', $this->composerRuntimeRequirement(root: $root, package: 'ext-pdo'));
            self::assertSame('*', $this->composerRuntimeRequirement(root: $root, package: 'ext-pdo_sqlite'));
            self::assertStringContainsString('Updated composer.json runtime requirements:', $output);
            self::assertStringContainsString("composer update\n", $output . "\n");
            self::assertSame('apps/todo/src/', $this->composerPsr4Path(root: $root, namespace: 'App\\Todo\\'));
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

            $indexController = $this->read(path: $root . '/apps/root-todo/src/Presentation/Http/Controller/Todo/Index/Controller.php');
            self::assertStringContainsString("'basePath' => ''", $indexController);

            $storeController = $this->read(path: $root . '/apps/root-todo/src/Presentation/Http/Controller/Todo/Store/Controller.php');
            self::assertStringContainsString("Response::redirect(location: '/')", $storeController);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testLayeredTemplateIncludesMiddlewareCommentAndExample(): void
    {
        $root = $this->temporaryRoot();

        try {
            $output = $this->runScaffold(root: $root, name: 'layered-app', pathPrefix: '/layered', template: 'layered');

            self::assertFileExists($root . '/apps/layered-app/src/Infrastructure/Database/ConnectionFactory.php');
            self::assertStringContainsString(
                'Registered application in apps/kernel/config/application.php: layered-app: /layered',
                $output,
            );
            self::assertFileExists($root . '/apps/layered-app/templates/contact.latte');
            self::assertFileExists(
                $root . '/apps/layered-app/src/Presentation/Http/Controller/Contact/Show/Controller.php',
            );
            $container = $this->read(path: $root . '/apps/layered-app/config/container.php');
            self::assertStringNotContainsString("require \$root . '/apps/kernel/config/container.php'", $container);
            self::assertStringContainsString("dsn: 'sqlite:'", $container);
            self::assertStringContainsString('ConnectionFactory', $container);
            self::assertStringContainsString('ITemplateRenderer::class', $container);
            self::assertStringContainsString('LatteTemplateRenderer', $container);

            $routes = $this->read(path: $root . '/apps/layered-app/config/routes.php');
            self::assertStringContainsString("['GET', '/layered/contact', ContactShowController::class]", $routes);
            self::assertStringContainsString("['POST', '/layered/contact', ContactSubmitController::class]", $routes);

            $showController = $this->read(
                path: $root . '/apps/layered-app/src/Presentation/Http/Controller/Contact/Show/Controller.php',
            );
            self::assertStringContainsString('private Csrf $csrf', $showController);
            self::assertStringContainsString("'csrfToken' => \$this->csrf->token()", $showController);
            $contactTemplate = $this->read(path: $root . '/apps/layered-app/templates/contact.latte');
            self::assertStringContainsString('name="_csrf" value="{$csrfToken}"', $contactTemplate);

            $middleware = $this->read(path: $root . '/apps/layered-app/config/middleware.php');
            self::assertStringContainsString('CSRF protection', $middleware);
            self::assertStringContainsString('split CSRF per app when needed', $middleware);
            self::assertStringNotContainsString('CSRF_EXCLUDED_PATHS', $middleware);
            self::assertStringContainsString('Add app-specific middleware here.', $middleware);
            self::assertStringContainsString('AppNameHeaderMiddleware::class', $middleware);

            self::assertSame('^3.0', $this->composerRuntimeRequirement(root: $root, package: 'latte/latte'));
            self::assertSame('*', $this->composerRuntimeRequirement(root: $root, package: 'ext-pdo'));
            self::assertSame('*', $this->composerRuntimeRequirement(root: $root, package: 'ext-pdo_sqlite'));
            self::assertStringContainsString('Updated composer.json runtime requirements:', $output);
            self::assertSame(
                'apps/layered-app/src/',
                $this->composerPsr4Path(root: $root, namespace: 'App\\LayeredApp\\'),
            );
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testSharedDependentTemplateFailsBeforeWritingWhenSharedScaffoldIsMissing(): void
    {
        $root = $this->temporaryRoot();

        try {
            unlink($root . '/apps/shared/config/container.php');
            $result = $this->executeScaffold(
                root: $root,
                name: 'todo',
                pathPrefix: '/todo',
                template: 'todo',
            );

            self::assertSame(1, $result['status']);
            self::assertStringContainsString('requires the Shared scaffold', $result['output']);
            self::assertDirectoryDoesNotExist($root . '/apps/todo');
            self::assertNull($this->composerPsr4Path(root: $root, namespace: 'App\\Todo\\'));
            self::assertStringNotContainsString("name: 'todo'", $this->kernelApplicationConfig(root: $root));
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testUnsafePathPrefixFailsBeforeWritingProjectFiles(): void
    {
        $root = $this->temporaryRoot();

        try {
            $composerBefore = $this->read(path: $root . '/composer.json');
            $kernelBefore = $this->kernelApplicationConfig(root: $root);
            $result = $this->executeScaffold(
                root: $root,
                name: 'unsafe',
                pathPrefix: "/unsafe'path",
                template: 'bare',
            );

            self::assertSame(1, $result['status']);
            self::assertStringContainsString('Path prefix must contain only URL path segments', $result['output']);
            self::assertDirectoryDoesNotExist($root . '/apps/unsafe');
            self::assertSame($composerBefore, $this->read(path: $root . '/composer.json'));
            self::assertSame($kernelBefore, $this->kernelApplicationConfig(root: $root));
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testReservedAppNamesFailBeforeWritingProjectFiles(): void
    {
        foreach (['kernel', 'shared'] as $name) {
            $root = $this->temporaryRoot();

            try {
                $composerBefore = $this->read(path: $root . '/composer.json');
                $kernelBefore = $this->kernelApplicationConfig(root: $root);
                $sharedContainerBefore = $this->read(path: $root . '/apps/shared/config/container.php');
                $sharedMiddlewareBefore = $this->read(path: $root . '/apps/shared/config/middleware.php');

                $result = $this->executeScaffold(
                    root: $root,
                    name: $name,
                    pathPrefix: '/' . $name,
                    template: 'bare',
                );

                self::assertSame(1, $result['status']);
                self::assertStringContainsString("App name '{$name}' is reserved.", $result['output']);
                self::assertSame($composerBefore, $this->read(path: $root . '/composer.json'));
                self::assertSame($kernelBefore, $this->kernelApplicationConfig(root: $root));
                self::assertSame(
                    $sharedContainerBefore,
                    $this->read(path: $root . '/apps/shared/config/container.php'),
                );
                self::assertSame(
                    $sharedMiddlewareBefore,
                    $this->read(path: $root . '/apps/shared/config/middleware.php'),
                );
            } finally {
                $this->removeDirectory(path: $root);
            }
        }
    }

    private function temporaryRoot(): string
    {
        $root = sys_get_temp_dir() . '/webmedia-scaffold-app-test-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($root, recursive: true));
        self::assertNotFalse(file_put_contents(
            $root . '/composer.json',
            "{\n  \"name\": \"example/app\",\n  \"require\": {\n    \"php\": \"^8.5\"\n  }\n}\n",
        ));
        self::assertTrue(mkdir($root . '/apps/shared/config', recursive: true));
        self::assertNotFalse(file_put_contents(
            $root . '/apps/shared/config/container.php',
            "<?php\n\nreturn static function (): void {};\n",
        ));
        self::assertNotFalse(file_put_contents(
            $root . '/apps/shared/config/middleware.php',
            "<?php\n\nreturn [];\n",
        ));
        self::assertTrue(mkdir($root . '/apps/kernel/config', recursive: true));
        $kernelTemplate = file_get_contents(
            dirname(__DIR__, 2) . '/templates/kernel/config/application.php',
        );
        self::assertIsString($kernelTemplate);
        self::assertNotFalse(file_put_contents(
            $root . '/apps/kernel/config/application.php',
            $kernelTemplate,
        ));

        return $root;
    }

    private function composerRuntimeRequirement(string $root, string $package): ?string
    {
        $contents = $this->read(path: $root . '/composer.json');
        $composer = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);
        $requirements = $composer['require'] ?? [];
        self::assertIsArray($requirements);
        $constraint = $requirements[$package] ?? null;
        self::assertTrue($constraint === null || is_string($constraint));

        return $constraint;
    }

    private function composerPsr4Path(string $root, string $namespace): ?string
    {
        $contents = $this->read(path: $root . '/composer.json');
        $composer = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);
        $autoload = $composer['autoload'] ?? [];
        self::assertIsArray($autoload);
        $psr4 = $autoload['psr-4'] ?? [];
        self::assertIsArray($psr4);
        $path = $psr4[$namespace] ?? null;
        self::assertTrue($path === null || is_string($path));

        return $path;
    }

    private function kernelApplicationConfig(string $root): string
    {
        return $this->read(path: $root . '/apps/kernel/config/application.php');
    }

    private function runScaffold(string $root, string $name, string $pathPrefix, string $template): string
    {
        $result = $this->executeScaffold(
            root: $root,
            name: $name,
            pathPrefix: $pathPrefix,
            template: $template,
        );

        self::assertSame(0, $result['status'], $result['output']);

        return $result['output'];
    }

    /** @return array{status:int, output:string} */
    private function executeScaffold(string $root, string $name, string $pathPrefix, string $template): array
    {
        $packageRoot = dirname(__DIR__, 2);
        $command = implode(' ', [
            escapeshellarg(PHP_BINARY),
            escapeshellarg($packageRoot . '/bin/scaffold-app'),
            escapeshellarg($name),
            escapeshellarg($pathPrefix),
            '--template=' . escapeshellarg($template),
            '--root=' . escapeshellarg($root),
            '2>&1',
        ]);

        $lines = [];
        exec($command, $lines, $status);
        $output = implode("\n", $lines);

        return ['status' => $status, 'output' => $output];
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
