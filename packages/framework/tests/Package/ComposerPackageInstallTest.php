<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Package;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class ComposerPackageInstallTest extends TestCase
{
    public function testCopiedPackageInstallsWithoutGeneratingProjectFiles(): void
    {
        $temporaryRoot = sys_get_temp_dir() . '/pera-package-only-install-' . bin2hex(random_bytes(4));
        $packageRoot = $temporaryRoot . '/package';
        $consumerRoot = $temporaryRoot . '/consumer';
        self::assertTrue(mkdir($temporaryRoot));

        try {
            $this->copyDirectory(
                source: dirname(__DIR__, 2),
                destination: $packageRoot,
            );
            self::assertTrue(mkdir($consumerRoot));
            $this->writeConsumerManifest(path: $consumerRoot . '/composer.json', packageRoot: $packageRoot);

            $this->runCommand(
                command: ['composer', 'install', '--no-interaction', '--no-progress', '--no-scripts', '--prefer-dist'],
                workingDirectory: $consumerRoot,
            );

            self::assertDirectoryExists($consumerRoot . '/vendor/pera/framework');
            self::assertFalse(is_link($consumerRoot . '/vendor/pera/framework'));
            self::assertFileExists($consumerRoot . '/vendor/autoload.php');
            self::assertFileExists($consumerRoot . '/vendor/pera/framework/LICENSE');
            $installedManifestContents = file_get_contents($consumerRoot . '/vendor/pera/framework/composer.json');
            self::assertIsString($installedManifestContents);
            $installedManifest = json_decode($installedManifestContents, true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($installedManifest);
            self::assertSame('MIT', $installedManifest['license'] ?? null);
            foreach (['scaffold-kernel', 'scaffold-shared', 'scaffold-app'] as $binary) {
                self::assertFileExists($consumerRoot . '/vendor/bin/' . $binary);
            }
            self::assertDirectoryDoesNotExist($consumerRoot . '/apps');

            $this->runCommand(
                command: [
                    PHP_BINARY,
                    '-r',
                    sprintf(
                        "require %s; new Pera\\Framework\\Container\\Container();",
                        var_export($consumerRoot . '/vendor/autoload.php', true),
                    ),
                ],
                workingDirectory: $consumerRoot,
            );

            $requirements = $this->packageRequirements(root: $consumerRoot);
            self::assertArrayNotHasKey('latte/latte', $requirements);
            self::assertArrayNotHasKey('vlucas/phpdotenv', $requirements);
            self::assertArrayNotHasKey('ext-pdo', $requirements);
        } finally {
            $this->removeDirectory(path: $temporaryRoot);
        }
    }

    public function testInstalledKernelScaffoldBuildsWithoutSharedOrAppScaffolds(): void
    {
        $temporaryRoot = sys_get_temp_dir() . '/pera-kernel-only-install-' . bin2hex(random_bytes(4));
        $packageRoot = $temporaryRoot . '/package';
        $consumerRoot = $temporaryRoot . '/consumer';
        self::assertTrue(mkdir($temporaryRoot));

        try {
            $this->copyDirectory(
                source: dirname(__DIR__, 2),
                destination: $packageRoot,
            );
            self::assertTrue(mkdir($consumerRoot));
            $this->writeConsumerManifest(path: $consumerRoot . '/composer.json', packageRoot: $packageRoot);

            $this->runCommand(
                command: ['composer', 'install', '--no-interaction', '--no-progress', '--no-scripts', '--prefer-dist'],
                workingDirectory: $consumerRoot,
            );
            $output = $this->runCommand(
                command: [PHP_BINARY, 'vendor/bin/scaffold-kernel'],
                workingDirectory: $consumerRoot,
            );

            self::assertStringContainsString('Created kernel scaffold: apps/kernel', $output);
            self::assertDirectoryExists($consumerRoot . '/apps/kernel');
            self::assertDirectoryDoesNotExist($consumerRoot . '/apps/shared');
            self::assertDirectoryDoesNotExist($consumerRoot . '/apps/bare');
            self::assertSame('^5.6', $this->consumerRequirement(root: $consumerRoot, package: 'vlucas/phpdotenv'));
            self::assertArrayNotHasKey('vlucas/phpdotenv', $this->packageRequirements(root: $consumerRoot));

            $this->runCommand(
                command: [
                    'composer',
                    'update',
                    'vlucas/phpdotenv',
                    '--with-all-dependencies',
                    '--no-interaction',
                    '--no-progress',
                    '--no-scripts',
                    '--prefer-dist',
                ],
                workingDirectory: $consumerRoot,
            );
            $this->lintGeneratedPhp(root: $consumerRoot . '/apps/kernel');
            $this->analyseGeneratedPhp(consumerRoot: $consumerRoot);
            $this->verifyGeneratedKernel(root: $consumerRoot);
        } finally {
            $this->removeDirectory(path: $temporaryRoot);
        }
    }

    public function testCopiedPackageInstallsAndAllVendorScaffoldsRunInConsumerProject(): void
    {
        $temporaryRoot = sys_get_temp_dir() . '/pera-package-install-' . bin2hex(random_bytes(4));
        $packageRoot = $temporaryRoot . '/package';
        $consumerRoot = $temporaryRoot . '/consumer';
        self::assertTrue(mkdir($temporaryRoot));

        try {
            $this->copyDirectory(
                source: dirname(__DIR__, 2),
                destination: $packageRoot,
            );
            self::assertTrue(mkdir($consumerRoot));
            $this->writeConsumerManifest(path: $consumerRoot . '/composer.json', packageRoot: $packageRoot);

            $this->runCommand(
                command: ['composer', 'install', '--no-interaction', '--no-progress', '--no-scripts', '--prefer-dist'],
                workingDirectory: $consumerRoot,
            );

            self::assertDirectoryExists($consumerRoot . '/vendor/pera/framework');
            self::assertFalse(is_link($consumerRoot . '/vendor/pera/framework'));
            foreach (['scaffold-kernel', 'scaffold-shared', 'scaffold-app'] as $binary) {
                self::assertFileExists($consumerRoot . '/vendor/bin/' . $binary);
            }

            $this->runCommand(command: [PHP_BINARY, 'vendor/bin/scaffold-kernel'], workingDirectory: $consumerRoot);
            $this->runCommand(command: [PHP_BINARY, 'vendor/bin/scaffold-shared'], workingDirectory: $consumerRoot);
            $this->runAppScaffold(root: $consumerRoot, name: 'bare', pathPrefix: '/bare', template: 'bare');
            $this->runAppScaffold(root: $consumerRoot, name: 'minimal', pathPrefix: '/minimal', template: 'minimal');
            $this->runAppScaffold(root: $consumerRoot, name: 'todo', pathPrefix: '/todo', template: 'todo');
            $this->runAppScaffold(root: $consumerRoot, name: 'layered', pathPrefix: '/layered', template: 'layered');

            $this->assertGeneratedRequirements(root: $consumerRoot);
            $this->runCommand(
                command: ['composer', 'update', '--no-interaction', '--no-progress', '--no-scripts', '--prefer-dist'],
                workingDirectory: $consumerRoot,
            );
            $this->lintGeneratedPhp(root: $consumerRoot . '/apps');
            $this->analyseGeneratedPhp(consumerRoot: $consumerRoot);
            $this->verifyGeneratedContainers(root: $consumerRoot);
        } finally {
            $this->removeDirectory(path: $temporaryRoot);
        }
    }

    private function writeConsumerManifest(string $path, string $packageRoot): void
    {
        $manifest = [
            'name' => 'example/pera-consumer',
            'repositories' => [[
                'type' => 'path',
                'url' => $packageRoot,
                'options' => [
                    'symlink' => false,
                    'versions' => ['pera/framework' => 'dev-main'],
                ],
            ]],
            'require' => [
                'php' => '>=8.5',
                'pera/framework' => 'dev-main',
            ],
            'autoload' => [
                'psr-4' => [
                    'App\\Kernel\\' => 'apps/kernel/src/',
                    'App\\Shared\\' => 'apps/shared/src/',
                    'App\\Bare\\' => 'apps/bare/src/',
                    'App\\Minimal\\' => 'apps/minimal/src/',
                    'App\\Todo\\' => 'apps/todo/src/',
                    'App\\Layered\\' => 'apps/layered/src/',
                ],
            ],
            'config' => ['sort-packages' => true],
        ];
        $contents = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        self::assertNotFalse(file_put_contents($path, $contents . "\n"));
    }

    private function runAppScaffold(string $root, string $name, string $pathPrefix, string $template): void
    {
        $this->runCommand(
            command: [
                PHP_BINARY,
                'vendor/bin/scaffold-app',
                $name,
                $pathPrefix,
                '--template=' . $template,
            ],
            workingDirectory: $root,
        );
    }

    private function assertGeneratedRequirements(string $root): void
    {
        $contents = file_get_contents($root . '/composer.json');
        self::assertIsString($contents);
        $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        $requirements = $manifest['require'] ?? [];
        self::assertIsArray($requirements);

        self::assertSame('^5.6', $requirements['vlucas/phpdotenv'] ?? null, $contents);
        self::assertSame('^3.0', $requirements['latte/latte'] ?? null);
        self::assertSame('*', $requirements['ext-pdo'] ?? null);
        self::assertSame('*', $requirements['ext-pdo_sqlite'] ?? null);
        self::assertArrayNotHasKey('ext-pdo', $this->packageRequirements(root: $root));
        self::assertArrayNotHasKey('latte/latte', $this->packageRequirements(root: $root));
        self::assertArrayNotHasKey('vlucas/phpdotenv', $this->packageRequirements(root: $root));
    }

    private function consumerRequirement(string $root, string $package): ?string
    {
        $contents = file_get_contents($root . '/composer.json');
        self::assertIsString($contents);
        $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        $requirements = $manifest['require'] ?? [];
        self::assertIsArray($requirements);
        $constraint = $requirements[$package] ?? null;
        self::assertTrue($constraint === null || is_string($constraint));

        return $constraint;
    }

    /** @return array<string, mixed> */
    private function packageRequirements(string $root): array
    {
        $contents = file_get_contents($root . '/vendor/pera/framework/composer.json');
        self::assertIsString($contents);
        $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        $requirements = $manifest['require'] ?? [];
        self::assertIsArray($requirements);

        $stringRequirements = [];
        foreach ($requirements as $package => $constraint) {
            if (is_string($package)) {
                $stringRequirements[$package] = $constraint;
            }
        }

        return $stringRequirements;
    }

    private function lintGeneratedPhp(string $root): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $this->runCommand(command: [PHP_BINARY, '-l', $file->getPathname()], workingDirectory: $root);
        }
    }

    private function verifyGeneratedContainers(string $root): void
    {
        $verificationPath = $root . '/verify-generated.php';
        self::assertNotFalse(file_put_contents($verificationPath, <<<'PHP'
<?php

declare(strict_types=1);

use App\Layered\Application\Health\Show\Input as HealthInput;
use App\Layered\Application\Health\Show\Interactor as HealthInteractor;
use App\Todo\Application\Todo\ITodoRepository;
use Pera\Framework\Container\Container;
use Pera\Framework\Http\Middleware\CallableRequestHandler;
use Pera\Framework\Http\Middleware\MiddlewarePipeline;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Routing\Router;
use Pera\Framework\Security\CsrfMiddleware;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use Pera\Framework\Security\SessionState;

require __DIR__ . '/vendor/autoload.php';

$bare = require __DIR__ . '/apps/bare/config/container.php';
if (!$bare instanceof Container) {
    throw new RuntimeException('Bare scaffold did not return a Container.');
}
$bareRoutes = require __DIR__ . '/apps/bare/config/routes.php';
$bareResponse = (new Router(routes: $bareRoutes))->dispatch(
    request: new Request(method: 'GET', path: '/bare', query: [], body: [], server: []),
    container: $bare,
);
if (
    $bareResponse->status !== Response::HTTP_OK
    || $bareResponse->body !== 'Hello from bare.'
    || ($bareResponse->headers['Content-Type'] ?? null) !== 'text/plain; charset=utf-8'
) {
    throw new RuntimeException('Bare scaffold did not return its first plain-text HTTP 200 response.');
}

$minimal = require __DIR__ . '/apps/minimal/config/container.php';
if (!$minimal instanceof Container) {
    throw new RuntimeException('Minimal scaffold did not return a Container.');
}
$minimalRoutes = require __DIR__ . '/apps/minimal/config/routes.php';
$response = (new Router(routes: $minimalRoutes))->dispatch(
    request: new Request(method: 'GET', path: '/minimal', query: [], body: [], server: []),
    container: $minimal,
);
if (!$response instanceof Response || $response->status !== Response::HTTP_OK || !str_contains($response->body, 'minimal is running.')) {
    throw new RuntimeException('Minimal scaffold did not return its first HTTP 200 response.');
}

$layered = require __DIR__ . '/apps/layered/config/container.php';
if (!$layered instanceof Container) {
    throw new RuntimeException('Layered scaffold did not return a Container.');
}
$health = $layered->get(HealthInteractor::class);
if (!$health instanceof HealthInteractor || $health->execute(new HealthInput())->toArray()['database'] !== 'ok') {
    throw new RuntimeException('Layered PDO composition is not executable.');
}

$todo = require __DIR__ . '/apps/todo/config/container.php';
if (!$todo instanceof Container) {
    throw new RuntimeException('Todo scaffold did not return a Container.');
}
$todos = $todo->get(ITodoRepository::class);
if (!$todos instanceof ITodoRepository) {
    throw new RuntimeException('Todo repository binding is not executable.');
}

$sessionData = [];
$todo->instance(
    id: Session::class,
    instance: new Session(
        state: new SessionState(data: $sessionData),
        runtime: new NativeSessionRuntime(),
    ),
);
$todoRoutes = require __DIR__ . '/apps/todo/config/routes.php';
$todoRouter = new Router(routes: $todoRoutes);
$todoMiddleware = require __DIR__ . '/apps/todo/config/middleware.php';
if (!is_array($todoMiddleware) || !in_array(CsrfMiddleware::class, $todoMiddleware, true)) {
    throw new RuntimeException('Todo scaffold did not import the shared CSRF middleware.');
}
$todoDestination = new CallableRequestHandler(
    handler: static fn (Request $request): Response => $todoRouter->dispatch(
        request: $request,
        container: $todo,
    ),
);
$pipeline = new MiddlewarePipeline();

$todoIndex = $pipeline->handle(
    request: new Request(method: 'GET', path: '/todo', query: [], body: [], server: []),
    container: $todo,
    middleware: $todoMiddleware,
    destination: $todoDestination,
);
$csrfToken = $sessionData['_csrf'] ?? null;
if (
    $todoIndex->status !== Response::HTTP_OK
    || !is_string($csrfToken)
    || $csrfToken === ''
    || substr_count($todoIndex->body, 'name="_csrf"') !== 1
    || !str_contains($todoIndex->body, 'value="' . $csrfToken . '"')
) {
    throw new RuntimeException('Todo scaffold did not render its CSRF-protected form.');
}

$rejected = $pipeline->handle(
    request: new Request(method: 'POST', path: '/todo', query: [], body: ['title' => 'rejected'], server: []),
    container: $todo,
    middleware: $todoMiddleware,
    destination: $todoDestination,
);
if ($rejected->status !== Response::HTTP_UNPROCESSABLE_ENTITY || count($todos->all()) !== 0) {
    throw new RuntimeException('Todo scaffold accepted a POST without a CSRF token.');
}

$accepted = $pipeline->handle(
    request: new Request(
        method: 'POST',
        path: '/todo',
        query: [],
        body: ['title' => 'package install smoke test', '_csrf' => $csrfToken],
        server: [],
    ),
    container: $todo,
    middleware: $todoMiddleware,
    destination: $todoDestination,
);
if ($accepted->status !== Response::HTTP_FOUND || count($todos->all()) !== 1) {
    throw new RuntimeException('Todo scaffold did not accept a valid CSRF-protected POST.');
}
PHP));

        $this->runCommand(command: [PHP_BINARY, $verificationPath], workingDirectory: $root);
    }

    private function verifyGeneratedKernel(string $root): void
    {
        $verificationPath = $root . '/verify-kernel.php';
        self::assertNotFalse(file_put_contents($verificationPath, <<<'PHP'
<?php

declare(strict_types=1);

use Pera\Framework\Application\IApplication;
use Pera\Framework\Bootstrap\ApplicationBootstrapConfig;

require __DIR__ . '/vendor/autoload.php';

$applicationFactory = require __DIR__ . '/apps/kernel/config/application.php';
$application = $applicationFactory(new ApplicationBootstrapConfig(root: __DIR__));
if (!$application instanceof IApplication) {
    throw new RuntimeException('Kernel scaffold did not build an application.');
}
PHP));

        $this->runCommand(command: [PHP_BINARY, $verificationPath], workingDirectory: $root);
    }

    private function analyseGeneratedPhp(string $consumerRoot): void
    {
        $this->runCommand(
            command: [
                PHP_BINARY,
                $this->phpStanBinary(),
                'analyse',
                '--no-progress',
                '--level=max',
                '--autoload-file=' . $consumerRoot . '/vendor/autoload.php',
                $consumerRoot . '/apps',
            ],
            workingDirectory: $consumerRoot,
        );
    }

    private function phpStanBinary(): string
    {
        $packageRoot = dirname(__DIR__, 2);
        $candidates = [
            $packageRoot . '/vendor/bin/phpstan',
            dirname($packageRoot, 2) . '/vendor/bin/phpstan',
        ];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('PHPStan binary was not found. Run composer install first.');
    }

    /** @param list<string> $command */
    private function runCommand(array $command, string $workingDirectory): string
    {
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptorSpec, $pipes, $workingDirectory);
        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start command: ' . implode(' ', $command));
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if ($status !== 0) {
            self::fail(sprintf(
                "Command failed (%d): %s\n%s\n%s",
                $status,
                implode(' ', $command),
                is_string($stdout) ? $stdout : '',
                is_string($stderr) ? $stderr : '',
            ));
        }

        return is_string($stdout) ? $stdout : '';
    }

    private function copyDirectory(string $source, string $destination): void
    {
        self::assertTrue(mkdir($destination, recursive: true));
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            if ($this->isDevelopmentArtifact(relativePath: $relativePath)) {
                continue;
            }
            $target = $destination . '/' . $relativePath;
            if ($item->isDir()) {
                self::assertTrue(mkdir($target));
                continue;
            }
            self::assertTrue(copy($item->getPathname(), $target));
            chmod($target, $item->getPerms() & 0777);
        }
    }

    private function isDevelopmentArtifact(string $relativePath): bool
    {
        $topLevelPath = explode('/', $relativePath, 2)[0];

        return in_array($topLevelPath, ['.git', '.phpunit.cache', 'vendor'], true)
            || in_array($relativePath, ['.phpunit.result.cache', 'composer.lock'], true);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
