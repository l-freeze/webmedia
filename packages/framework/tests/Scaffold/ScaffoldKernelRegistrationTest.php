<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Scaffold;

use Closure;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ScaffoldKernelRegistrationTest extends TestCase
{
    public function testStandardKernelConfigurationRegistersApplicationInsideManagedMarkers(): void
    {
        $root = $this->temporaryRoot();

        try {
            $applicationPath = $this->writeStandardKernel(root: $root);
            $result = $this->register(
                root: $root,
                name: 'todo',
                pathPrefix: '/todo',
                definition: $this->definition(name: 'todo', pathPrefix: '/todo'),
            );

            self::assertTrue($result['registered']);
            $contents = $this->read(path: $applicationPath);
            self::assertStringContainsString("name: 'todo'", $contents);
            self::assertStringContainsString("pathPrefix: '/todo'", $contents);
            self::assertLessThan(
                strpos($contents, '// </pera:applications>'),
                strpos($contents, "name: 'todo'"),
            );
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testCustomKernelConfigurationIsLeftUntouchedWithManualInstructions(): void
    {
        $root = $this->temporaryRoot();

        try {
            self::assertTrue(mkdir($root . '/apps/kernel/config', recursive: true));
            $applicationPath = $root . '/apps/kernel/config/application.php';
            $original = "<?php\n\nreturn static fn (): array => [];\n";
            self::assertNotFalse(file_put_contents($applicationPath, $original));

            $result = $this->register(
                root: $root,
                name: 'todo',
                pathPrefix: '/todo',
                definition: $this->definition(name: 'todo', pathPrefix: '/todo'),
            );

            self::assertFalse($result['registered']);
            self::assertSame($original, $this->read(path: $applicationPath));
            self::assertStringContainsString(
                'Custom Kernel configuration detected',
                implode("\n", $result['messages']),
            );
            self::assertStringContainsString("name: 'todo'", implode("\n", $result['messages']));
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testDuplicatePathPrefixIsRejected(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeStandardKernel(root: $root);
            $this->register(
                root: $root,
                name: 'first',
                pathPrefix: '/same',
                definition: $this->definition(name: 'first', pathPrefix: '/same'),
            );

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('name or path prefix is already registered');
            $this->register(
                root: $root,
                name: 'second',
                pathPrefix: '/same',
                definition: $this->definition(name: 'second', pathPrefix: '/same'),
            );
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    private function temporaryRoot(): string
    {
        $root = sys_get_temp_dir() . '/pera-kernel-registration-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($root, recursive: true));

        return $root;
    }

    private function writeStandardKernel(string $root): string
    {
        self::assertTrue(mkdir($root . '/apps/kernel/config', recursive: true));
        $applicationPath = $root . '/apps/kernel/config/application.php';
        $template = file_get_contents(dirname(__DIR__, 2) . '/templates/kernel/config/application.php');
        self::assertIsString($template);
        self::assertNotFalse(file_put_contents($applicationPath, $template));

        return $applicationPath;
    }

    /**
     * @return array{registered: bool, messages: list<string>}
     */
    private function register(string $root, string $name, string $pathPrefix, string $definition): array
    {
        /**
         * @var Closure(string, string, string, string): array{
         *     registered: bool,
         *     messages: list<string>
         * } $register
         */
        $register = require dirname(__DIR__, 2) . '/bin/scaffold-kernel-registration.php';

        return $register($root, $name, $pathPrefix, $definition);
    }

    private function definition(string $name, string $pathPrefix): string
    {
        return <<<PHP
new ApplicationDefinition(
    name: '{$name}',
    pathPrefix: '{$pathPrefix}',
    routesPath: '/apps/{$name}/config/routes.php',
    containerPath: '/apps/{$name}/config/container.php',
    middlewarePath: '/apps/{$name}/config/middleware.php',
),
PHP;
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
