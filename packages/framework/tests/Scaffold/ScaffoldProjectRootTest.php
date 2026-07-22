<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Scaffold;

use PHPUnit\Framework\TestCase;

final class ScaffoldProjectRootTest extends TestCase
{
    public function testComposerVendorBinaryGeneratesIntoRootProject(): void
    {
        $root = $this->temporaryRoot();
        $packageRoot = dirname(__DIR__, 2);

        try {
            mkdir($root . '/vendor', 0777, true);
            file_put_contents($root . '/vendor/autoload.php', "<?php\n");
            file_put_contents($root . '/composer.json', "{\n  \"name\": \"example/app\",\n  \"require\": {\n    \"php\": \"^8.5\"\n  }\n}\n");
            $proxy = $root . '/composer-bin-proxy.php';
            file_put_contents($proxy, sprintf(
                "<?php\n\$GLOBALS['_composer_autoload_path'] = %s;\nrequire %s;\n",
                var_export($root . '/vendor/autoload.php', true),
                var_export($packageRoot . '/bin/scaffold-kernel', true),
            ));

            $output = $this->executeCommand(command: [PHP_BINARY, $proxy]);

            self::assertStringContainsString('Created kernel scaffold: apps/kernel', $output);
            self::assertFileExists($root . '/apps/kernel/config/application.php');
            self::assertFileExists($root . '/apps/kernel/src/Application.php');
            $composer = json_decode($this->read(path: $root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($composer);
            $requirements = $composer['require'] ?? [];
            self::assertIsArray($requirements);
            self::assertSame('^5.6', $requirements['vlucas/phpdotenv'] ?? null);
            $application = $this->read(path: $root . '/apps/kernel/src/Application.php');
            self::assertStringContainsString(
                'use Pera\\Framework\\Application\\IApplication;',
                $application,
            );
            self::assertStringContainsString('Session::fromNativeGlobals(', $application);
            self::assertStringContainsString('catch (MalformedRequestException)', $application);
            self::assertStringContainsString('$router->withRouteMetadata(request: $request)', $application);
            self::assertStringNotContainsString('sessionRuntime->start(', $application);
            self::assertStringNotContainsString('SessionState', $application);
            $readme = $this->read(path: $root . '/apps/kernel/README.md');
            self::assertStringContainsString(
                'constructor-injecting `Pera\\Framework\\Security\\Session`',
                $readme,
            );
            self::assertStringContainsString(
                'App scaffolds intentionally do not generate a stateful Controller by default',
                $readme,
            );
            self::assertFileDoesNotExist($packageRoot . '/apps/kernel/config/application.php');
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testDirectPackageExecutionUsesRepositoryWorkingDirectory(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        /** @var \Closure(?string, string, ?string): string $resolveProjectRoot */
        $resolveProjectRoot = require $packageRoot . '/bin/scaffold-project-root.php';

        self::assertSame(
            $packageRoot,
            $resolveProjectRoot(null, $packageRoot, $packageRoot),
        );
    }

    public function testComposerProxyAutoloadPathIsCanonicalizedBeforeResolvingRoot(): void
    {
        $root = $this->temporaryRoot();

        try {
            self::assertTrue(mkdir($root . '/vendor/bin', recursive: true));
            self::assertNotFalse(file_put_contents($root . '/vendor/autoload.php', "<?php\n"));
            /** @var \Closure(?string, string, ?string): string $resolveProjectRoot */
            $resolveProjectRoot = require dirname(__DIR__, 2) . '/bin/scaffold-project-root.php';

            self::assertSame(
                $root,
                $resolveProjectRoot(
                    $root . '/vendor/bin/../autoload.php',
                    '/tmp/vendor/pera/framework',
                    '/tmp/ignored-working-directory',
                ),
            );
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testPackagedScriptDirectExecutionFallsBackToWorkingDirectory(): void
    {
        /** @var \Closure(?string, string, ?string): string $resolveProjectRoot */
        $resolveProjectRoot = require dirname(__DIR__, 2) . '/bin/scaffold-project-root.php';

        self::assertSame(
            '/tmp/consumer-project',
            $resolveProjectRoot(null, '/tmp/vendor/pera/framework', '/tmp/consumer-project/'),
        );
    }

    /** @param list<string> $command */
    private function executeCommand(array $command): string
    {
        $escaped = array_map(static fn (string $part): string => escapeshellarg($part), $command);
        $lines = [];
        exec(implode(' ', $escaped) . ' 2>&1', $lines, $status);
        $output = implode("\n", $lines);

        self::assertSame(0, $status, $output);

        return $output;
    }

    private function temporaryRoot(): string
    {
        return sys_get_temp_dir() . '/webmedia-scaffold-project-root-test-' . bin2hex(random_bytes(4));
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
