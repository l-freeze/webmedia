<?php

declare(strict_types=1);

namespace Tests\Framework;

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
                source: dirname(__DIR__, 2) . '/packages/framework',
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

    public function testCopiedPackageInstallsAndAllVendorScaffoldsRunInConsumerProject(): void
    {
        $temporaryRoot = sys_get_temp_dir() . '/pera-package-install-' . bin2hex(random_bytes(4));
        $packageRoot = $temporaryRoot . '/package';
        $consumerRoot = $temporaryRoot . '/consumer';
        self::assertTrue(mkdir($temporaryRoot));

        try {
            $projectRoot = dirname(__DIR__, 2);
            $this->copyDirectory(
                source: $projectRoot . '/packages/framework',
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
            $this->analyseGeneratedPhp(projectRoot: $projectRoot, consumerRoot: $consumerRoot);
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
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Routing\Router;

require __DIR__ . '/vendor/autoload.php';

$bare = require __DIR__ . '/apps/bare/config/container.php';
if (!$bare instanceof Container) {
    throw new RuntimeException('Bare scaffold did not return a Container.');
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
$todos->add('package install smoke test');
if (count($todos->all()) !== 1) {
    throw new RuntimeException('Todo SQLite persistence is not executable.');
}
PHP));

        $this->runCommand(command: [PHP_BINARY, $verificationPath], workingDirectory: $root);
    }

    private function analyseGeneratedPhp(string $projectRoot, string $consumerRoot): void
    {
        $this->runCommand(
            command: [
                PHP_BINARY,
                $projectRoot . '/vendor/bin/phpstan',
                'analyse',
                '--no-progress',
                '--level=max',
                '--autoload-file=' . $consumerRoot . '/vendor/autoload.php',
                $consumerRoot . '/apps',
            ],
            workingDirectory: $consumerRoot,
        );
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
            $target = $destination . '/' . $relativePath;
            if ($item->isDir()) {
                self::assertTrue(mkdir($target));
                continue;
            }
            self::assertTrue(copy($item->getPathname(), $target));
            chmod($target, $item->getPerms() & 0777);
        }
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
