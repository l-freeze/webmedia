<?php

declare(strict_types=1);

namespace Tests\Scaffold;

use PHPUnit\Framework\TestCase;

final class ScaffoldProjectRootTest extends TestCase
{
    public function testComposerVendorBinaryGeneratesIntoRootProject(): void
    {
        $root = $this->temporaryRoot();
        $projectRoot = dirname(__DIR__, 2);

        try {
            mkdir($root . '/vendor', 0777, true);
            file_put_contents($root . '/vendor/autoload.php', "<?php\n");
            $proxy = $root . '/composer-bin-proxy.php';
            file_put_contents($proxy, sprintf(
                "<?php\n\$GLOBALS['_composer_autoload_path'] = %s;\nrequire %s;\n",
                var_export($root . '/vendor/autoload.php', true),
                var_export($projectRoot . '/packages/framework/bin/scaffold-kernel', true),
            ));

            $output = $this->executeCommand(command: [PHP_BINARY, $proxy]);

            self::assertStringContainsString('Created kernel scaffold: apps/kernel', $output);
            self::assertFileExists($root . '/apps/kernel/config/application.php');
            self::assertFileExists($root . '/apps/kernel/src/Application.php');
            $application = $this->read(path: $root . '/apps/kernel/src/Application.php');
            self::assertStringContainsString(
                'use Pera\\Framework\\Application\\IApplication;',
                $application,
            );
            self::assertStringContainsString('Session::fromNativeGlobals(', $application);
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
            self::assertFileDoesNotExist($projectRoot . '/vendor/pera/framework/apps/kernel/config/application.php');
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testDirectPackageExecutionUsesRepositoryWorkingDirectory(): void
    {
        /** @var \Closure(?string, string, ?string): string $resolveProjectRoot */
        $resolveProjectRoot = require dirname(__DIR__, 2) . '/packages/framework/bin/scaffold-project-root.php';
        $projectRoot = dirname(__DIR__, 2);
        $packageRoot = $projectRoot . '/packages/framework';

        self::assertSame(
            $projectRoot,
            $resolveProjectRoot(null, $packageRoot, $projectRoot),
        );
    }

    public function testPackagedScriptDirectExecutionFallsBackToWorkingDirectory(): void
    {
        /** @var \Closure(?string, string, ?string): string $resolveProjectRoot */
        $resolveProjectRoot = require dirname(__DIR__, 2) . '/packages/framework/bin/scaffold-project-root.php';

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
