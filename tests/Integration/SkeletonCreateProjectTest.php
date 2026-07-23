<?php

declare(strict_types=1);

namespace Tests\Integration;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class SkeletonCreateProjectTest extends TestCase
{
    public function testLocalPathPackageCreatesRunnableProjectAndKeepsScaffoldsAvailable(): void
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $temporaryRoot = sys_get_temp_dir() . '/pera-skeleton-create-project-' . bin2hex(random_bytes(4));
        $frameworkRoot = $temporaryRoot . '/framework';
        $skeletonRoot = $temporaryRoot . '/skeleton';
        $projectRoot = $temporaryRoot . '/example';
        self::assertTrue(mkdir($temporaryRoot));

        try {
            $sourceManifest = $this->manifest(path: $repositoryRoot . '/packages/skeleton/composer.json');
            self::assertSame('pera/skeleton', $sourceManifest['name'] ?? null);
            self::assertSame('project', $sourceManifest['type'] ?? null);
            self::assertSame('MIT', $sourceManifest['license'] ?? null);
            self::assertArrayNotHasKey('repositories', $sourceManifest);

            $this->copyDirectory(
                source: $repositoryRoot . '/packages/framework',
                destination: $frameworkRoot,
            );
            $this->copyDirectory(
                source: $repositoryRoot . '/packages/skeleton',
                destination: $skeletonRoot,
            );
            $this->addLocalFrameworkRepository(manifestPath: $skeletonRoot . '/composer.json', frameworkRoot: $frameworkRoot);

            $skeletonRepository = json_encode([
                'type' => 'path',
                'url' => $skeletonRoot,
                'options' => [
                    'symlink' => false,
                    'versions' => ['pera/skeleton' => '0.1.0'],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->runCommand(
                command: [
                    'composer',
                    'create-project',
                    '--repository=' . $skeletonRepository,
                    '--no-interaction',
                    '--no-progress',
                    '--prefer-dist',
                    'pera/skeleton',
                    $projectRoot,
                    '0.1.0',
                ],
                workingDirectory: $temporaryRoot,
            );

            self::assertDirectoryExists($projectRoot . '/apps/kernel');
            self::assertDirectoryExists($projectRoot . '/apps/shared');
            self::assertDirectoryExists($projectRoot . '/apps/minimal');
            self::assertFileExists($projectRoot . '/public/index.php');
            self::assertFileExists($projectRoot . '/compose.yaml');
            self::assertFileExists($projectRoot . '/composer.lock');
            self::assertDirectoryExists($projectRoot . '/vendor/pera/framework');
            self::assertFalse(is_link($projectRoot . '/vendor/pera/framework'));
            foreach (['scaffold-app', 'scaffold-kernel', 'scaffold-shared'] as $binary) {
                self::assertFileExists($projectRoot . '/vendor/bin/' . $binary);
            }
            $sharedMiddleware = file_get_contents($projectRoot . '/apps/shared/config/middleware.php');
            self::assertIsString($sharedMiddleware);
            self::assertStringContainsString('CsrfMiddleware::class', $sharedMiddleware);
            self::assertStringContainsString("add CsrfMiddleware::class to each browser/session app's", $sharedMiddleware);

            $requirements = $this->requirements(manifestPath: $projectRoot . '/composer.json');
            self::assertSame('^0.1', $requirements['pera/framework'] ?? null);
            self::assertSame('^5.6', $requirements['vlucas/phpdotenv'] ?? null);
            self::assertSame('^3.0', $requirements['latte/latte'] ?? null);
            self::assertArrayNotHasKey('ext-pdo', $requirements);
            self::assertArrayNotHasKey('ext-pdo_sqlite', $requirements);

            $this->runCommand(command: ['composer', 'validate', '--strict'], workingDirectory: $projectRoot);
            $this->runCommand(command: ['composer', 'test'], workingDirectory: $projectRoot);
            $this->runCommand(command: ['composer', 'analyse'], workingDirectory: $projectRoot);

            $output = $this->runCommand(
                command: [PHP_BINARY, 'public/index.php'],
                workingDirectory: $projectRoot,
            );
            self::assertStringContainsString('minimal is running.', $output);
        } finally {
            $this->removeDirectory(path: $temporaryRoot);
        }
    }

    #[DataProvider('appScaffoldProvider')]
    public function testFreshLocalPathProjectRegistersAndRunsAppScaffold(
        string $name,
        string $pathPrefix,
        string $template,
    ): void {
        $project = $this->createFreshLocalPathProject();
        $temporaryRoot = $project['temporaryRoot'];
        $projectRoot = $project['projectRoot'];

        try {
            $output = $this->runCommand(
                command: [
                    PHP_BINARY,
                    'vendor/bin/scaffold-app',
                    $name,
                    $pathPrefix,
                    '--template=' . $template,
                ],
                workingDirectory: $projectRoot,
            );

            self::assertStringContainsString("Created app scaffold: apps/{$name} ({$template})", $output);
            self::assertStringContainsString(
                "Registered application in apps/kernel/config/application.php: {$name}: {$pathPrefix}",
                $output,
            );
            self::assertStringContainsString('Updated composer.json PSR-4 autoload:', $output);
            self::assertDirectoryExists($projectRoot . '/apps/' . $name);

            $namespace = $this->namespaceName(name: $name);
            self::assertSame(
                'apps/' . $name . '/src/',
                $this->psr4Path(manifestPath: $projectRoot . '/composer.json', namespace: 'App\\' . $namespace . '\\'),
            );
            $kernelConfig = file_get_contents($projectRoot . '/apps/kernel/config/application.php');
            self::assertIsString($kernelConfig);
            self::assertStringContainsString("name: '{$name}'", $kernelConfig);
            self::assertStringContainsString("pathPrefix: '{$pathPrefix}'", $kernelConfig);

            $this->runCommand(
                command: [
                    'composer',
                    'update',
                    '--no-interaction',
                    '--no-progress',
                    '--no-scripts',
                    '--prefer-dist',
                ],
                workingDirectory: $projectRoot,
            );
            $this->runCommand(command: ['composer', 'validate', '--strict'], workingDirectory: $projectRoot);
            $this->runCommand(command: ['composer', 'test'], workingDirectory: $projectRoot);
            $this->runCommand(command: ['composer', 'analyse'], workingDirectory: $projectRoot);

            $verificationPath = $projectRoot . '/verify-scaffold.php';
            self::assertNotFalse(file_put_contents($verificationPath, $this->scaffoldVerifier()));
            $this->runCommand(
                command: [PHP_BINARY, $verificationPath, $template, $pathPrefix],
                workingDirectory: $projectRoot,
            );
        } finally {
            $this->removeDirectory(path: $temporaryRoot);
        }
    }

    /** @return iterable<string, array{name:string,pathPrefix:string,template:string}> */
    public static function appScaffoldProvider(): iterable
    {
        yield 'bare' => ['name' => 'bare', 'pathPrefix' => '/bare', 'template' => 'bare'];
        yield 'minimal' => ['name' => 'site', 'pathPrefix' => '/site', 'template' => 'minimal'];
        yield 'todo' => ['name' => 'todo', 'pathPrefix' => '/todo', 'template' => 'todo'];
        yield 'layered' => ['name' => 'admin', 'pathPrefix' => '/admin', 'template' => 'layered'];
    }

    /** @return array{temporaryRoot:string, projectRoot:string} */
    private function createFreshLocalPathProject(): array
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $temporaryRoot = sys_get_temp_dir() . '/pera-skeleton-scaffold-' . bin2hex(random_bytes(4));
        $frameworkRoot = $temporaryRoot . '/framework';
        $skeletonRoot = $temporaryRoot . '/skeleton';
        $projectRoot = $temporaryRoot . '/example';
        self::assertTrue(mkdir($temporaryRoot));

        $this->copyDirectory(
            source: $repositoryRoot . '/packages/framework',
            destination: $frameworkRoot,
        );
        $this->copyDirectory(
            source: $repositoryRoot . '/packages/skeleton',
            destination: $skeletonRoot,
        );
        $this->addLocalFrameworkRepository(
            manifestPath: $skeletonRoot . '/composer.json',
            frameworkRoot: $frameworkRoot,
        );

        $skeletonRepository = json_encode([
            'type' => 'path',
            'url' => $skeletonRoot,
            'options' => [
                'symlink' => false,
                'versions' => ['pera/skeleton' => '0.1.0'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->runCommand(
            command: [
                'composer',
                'create-project',
                '--repository=' . $skeletonRepository,
                '--no-interaction',
                '--no-progress',
                '--prefer-dist',
                'pera/skeleton',
                $projectRoot,
                '0.1.0',
            ],
            workingDirectory: $temporaryRoot,
        );

        self::assertDirectoryExists($projectRoot . '/vendor/pera/framework');
        self::assertFalse(is_link($projectRoot . '/vendor/pera/framework'));

        return ['temporaryRoot' => $temporaryRoot, 'projectRoot' => $projectRoot];
    }

    private function namespaceName(string $name): string
    {
        $namespace = '';
        foreach (explode('-', $name) as $part) {
            $namespace .= ucfirst($part);
        }

        return $namespace;
    }

    private function psr4Path(string $manifestPath, string $namespace): ?string
    {
        $manifest = $this->manifest(path: $manifestPath);
        $autoload = $manifest['autoload'] ?? [];
        self::assertIsArray($autoload);
        $psr4 = $autoload['psr-4'] ?? [];
        self::assertIsArray($psr4);
        $path = $psr4[$namespace] ?? null;
        self::assertTrue($path === null || is_string($path));

        return $path;
    }

    private function scaffoldVerifier(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use App\Kernel\Application;
use Pera\Framework\Bootstrap\ApplicationBootstrapConfig;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use Pera\Framework\Security\SessionState;

require __DIR__ . '/vendor/autoload.php';

$template = $argv[1] ?? '';
$pathPrefix = $argv[2] ?? '';
$applicationFactory = require __DIR__ . '/apps/kernel/config/application.php';
$application = $applicationFactory(new ApplicationBootstrapConfig(root: __DIR__));
if (!$application instanceof Application) {
    throw new RuntimeException('Skeleton Kernel did not build its concrete Application.');
}

$sessionData = [];
$session = new Session(
    state: new SessionState(data: $sessionData),
    runtime: new NativeSessionRuntime(),
);

if ($template === 'bare') {
    $response = $application->handle(
        request: new Request(method: 'GET', path: $pathPrefix, query: [], body: [], server: []),
        session: $session,
    );
    if ($response->status !== Response::HTTP_OK || $response->body !== 'Hello from bare.') {
        throw new RuntimeException('Bare scaffold is not reachable through the generated Kernel.');
    }
    exit(0);
}

if ($template === 'minimal') {
    $response = $application->handle(
        request: new Request(method: 'GET', path: $pathPrefix, query: [], body: [], server: []),
        session: $session,
    );
    if (
        $response->status !== Response::HTTP_OK
        || !str_contains($response->body, 'site is running.')
        || $sessionData !== []
    ) {
        throw new RuntimeException('Minimal scaffold is not a stateless page through the generated Kernel.');
    }
    exit(0);
}

if ($template === 'todo') {
    $index = $application->handle(
        request: new Request(method: 'GET', path: $pathPrefix, query: [], body: [], server: []),
        session: $session,
    );
    $csrfToken = $sessionData['_csrf'] ?? null;
    if (
        $index->status !== Response::HTTP_OK
        || !is_string($csrfToken)
        || !str_contains($index->body, 'name="_csrf" value="' . $csrfToken . '"')
    ) {
        throw new RuntimeException('Todo scaffold did not render its CSRF form.');
    }

    $rejected = $application->handle(
        request: new Request(method: 'POST', path: $pathPrefix, query: [], body: ['title' => 'rejected'], server: []),
        session: $session,
    );
    if ($rejected->status !== Response::HTTP_UNPROCESSABLE_ENTITY) {
        throw new RuntimeException('Todo scaffold accepted a POST without CSRF.');
    }

    $accepted = $application->handle(
        request: new Request(
            method: 'POST',
            path: $pathPrefix,
            query: [],
            body: ['title' => 'fresh path E2E', '_csrf' => $csrfToken],
            server: [],
        ),
        session: $session,
    );
    if ($accepted->status !== Response::HTTP_FOUND) {
        throw new RuntimeException('Todo scaffold rejected a valid POST.');
    }
    exit(0);
}

if ($template === 'layered') {
    $health = $application->handle(
        request: new Request(method: 'GET', path: $pathPrefix . '/health', query: [], body: [], server: []),
        session: $session,
    );
    if (
        $health->status !== Response::HTTP_OK
        || !str_contains($health->body, '"database":"ok"')
        || ($health->headers['X-App-Name'] ?? null) !== 'admin'
    ) {
        throw new RuntimeException('Layered health route is not reachable through the generated Kernel.');
    }

    $form = $application->handle(
        request: new Request(method: 'GET', path: $pathPrefix . '/contact', query: [], body: [], server: []),
        session: $session,
    );
    $csrfToken = $sessionData['_csrf'] ?? null;
    if (
        $form->status !== Response::HTTP_OK
        || !is_string($csrfToken)
        || !str_contains($form->body, 'name="_csrf" value="' . $csrfToken . '"')
    ) {
        throw new RuntimeException('Layered scaffold did not render its CSRF form.');
    }

    $rejected = $application->handle(
        request: new Request(
            method: 'POST',
            path: $pathPrefix . '/contact',
            query: [],
            body: ['name' => 'Pera', 'email' => 'pera@example.com', 'message' => 'Hello'],
            server: [],
        ),
        session: $session,
    );
    if ($rejected->status !== Response::HTTP_UNPROCESSABLE_ENTITY) {
        throw new RuntimeException('Layered scaffold accepted a POST without CSRF.');
    }

    $accepted = $application->handle(
        request: new Request(
            method: 'POST',
            path: $pathPrefix . '/contact',
            query: [],
            body: [
                'name' => 'Pera',
                'email' => 'pera@example.com',
                'message' => 'Hello',
                '_csrf' => $csrfToken,
            ],
            server: [],
        ),
        session: $session,
    );
    if (
        $accepted->status !== Response::HTTP_OK
        || !str_contains($accepted->body, '送信内容を受け付けました。')
    ) {
        throw new RuntimeException('Layered scaffold rejected a valid POST.');
    }
    exit(0);
}

throw new RuntimeException('Unknown scaffold template: ' . $template);
PHP;
    }

    private function addLocalFrameworkRepository(string $manifestPath, string $frameworkRoot): void
    {
        $manifest = $this->manifest(path: $manifestPath);
        $manifest['repositories'] = [[
            'type' => 'path',
            'url' => $frameworkRoot,
            'options' => [
                'symlink' => false,
                'versions' => ['pera/framework' => '0.1.0'],
            ],
        ]];
        $contents = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        self::assertNotFalse(file_put_contents($manifestPath, $contents . "\n"));
    }

    /** @return array<string, mixed> */
    private function manifest(string $path): array
    {
        $contents = file_get_contents($path);
        self::assertIsString($contents);
        $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);

        $stringKeyedManifest = [];
        foreach ($manifest as $key => $value) {
            if (is_string($key)) {
                $stringKeyedManifest[$key] = $value;
            }
        }

        return $stringKeyedManifest;
    }

    /** @return array<string, mixed> */
    private function requirements(string $manifestPath): array
    {
        $manifest = $this->manifest(path: $manifestPath);
        $requirements = $manifest['require'] ?? null;
        self::assertIsArray($requirements);

        $stringKeyedRequirements = [];
        foreach ($requirements as $package => $constraint) {
            if (is_string($package)) {
                $stringKeyedRequirements[$package] = $constraint;
            }
        }

        return $stringKeyedRequirements;
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
