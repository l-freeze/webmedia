<?php

declare(strict_types=1);

namespace Tests\Scaffold;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ScaffoldTransactionTest extends TestCase
{
    public function testFailedGenerationRestoresComposerAndRemovesStagingFiles(): void
    {
        $root = sys_get_temp_dir() . '/pera-scaffold-transaction-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($root));
        $originalComposer = "{\n  \"name\": \"example/app\"\n}\n";
        self::assertNotFalse(file_put_contents($root . '/composer.json', $originalComposer));

        try {
            $helperPath = dirname(__DIR__, 2) . '/packages/framework/bin/scaffold-transaction.php';
            $runnerPath = $root . '/fail-generation.php';
            $runner = sprintf(<<<'PHP'
<?php

declare(strict_types=1);

$createTransaction = require %s;
$transaction = $createTransaction(%s, %s);
file_put_contents(%s, "{\n  \"changed\": true\n}\n");
file_put_contents($transaction->stagingRoot() . '/partial.php', "<?php\n");
exit(23);
PHP,
                var_export($helperPath, true),
                var_export($root, true),
                var_export($root . '/apps/example', true),
                var_export($root . '/composer.json', true),
            );
            self::assertNotFalse(file_put_contents($runnerPath, $runner));

            $lines = [];
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runnerPath) . ' 2>&1', $lines, $status);

            self::assertSame(23, $status, implode("\n", $lines));
            self::assertSame($originalComposer, file_get_contents($root . '/composer.json'));
            self::assertDirectoryDoesNotExist($root . '/apps/example');
            $stagingDirectories = glob($root . '/apps/.pera-*');
            self::assertIsArray($stagingDirectories);
            self::assertSame([], $stagingDirectories);
        } finally {
            $this->removeDirectory(path: $root);
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
