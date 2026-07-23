<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Scaffold;

use Closure;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ScaffoldComposerRequirementsTest extends TestCase
{
    public function testLatteFeatureAddsRuntimeRequirementWithoutCreatingLockFile(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeComposer($root, <<<'JSON'
{
  "name": "example/app",
  "require": {
    "php": "^8.5"
  }
}
JSON);

            $result = $this->sync(root: $root, features: ['latte']);

            self::assertSame(['latte/latte' => '^3.0'], $result['added']);
            self::assertSame('^3.0', $this->runtimeRequirement(root: $root, package: 'latte/latte'));
            self::assertFileDoesNotExist($root . '/composer.lock');
            self::assertContains('composer update', $result['messages']);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testExistingLockUsesTargetedUpdateCommand(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeComposer($root, <<<'JSON'
{
  "name": "example/app",
  "require": {
    "php": "^8.5"
  }
}
JSON);
            self::assertNotFalse(file_put_contents($root . '/composer.lock', "{}\n"));

            $result = $this->sync(root: $root, features: ['latte']);

            self::assertContains('composer update latte/latte', $result['messages']);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testMultipleFeaturesAddConsumerOwnedRuntimeRequirements(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeComposer($root, <<<'JSON'
{
  "name": "example/app",
  "require": {
    "php": "^8.5"
  }
}
JSON);

            $result = $this->sync(root: $root, features: ['dotenv', 'pdo-sqlite']);

            self::assertSame([
                'ext-pdo' => '*',
                'ext-pdo_sqlite' => '*',
                'vlucas/phpdotenv' => '^5.6',
            ], $result['added']);
            self::assertSame('*', $this->runtimeRequirement(root: $root, package: 'ext-pdo'));
            self::assertSame('*', $this->runtimeRequirement(root: $root, package: 'ext-pdo_sqlite'));
            self::assertSame('^5.6', $this->runtimeRequirement(root: $root, package: 'vlucas/phpdotenv'));
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testExistingRuntimeConstraintIsPreservedAndSecondRunIsIdempotent(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeComposer($root, <<<'JSON'
{
  "name": "example/app",
  "require": {
    "latte/latte": "^3.1",
    "php": "^8.5"
  }
}
JSON);
            $before = $this->read(path: $root . '/composer.json');

            $result = $this->sync(root: $root, features: ['latte']);

            self::assertSame([], $result['added']);
            self::assertSame(['latte/latte' => '^3.1'], $result['present']);
            self::assertSame([], $result['messages']);
            self::assertSame($before, $this->read(path: $root . '/composer.json'));
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testDevelopmentRequirementIsMovedToRuntimeWithoutChangingConstraint(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeComposer($root, <<<'JSON'
{
  "name": "example/app",
  "require": {
    "php": "^8.5"
  },
  "require-dev": {
    "latte/latte": "^3.2",
    "phpunit/phpunit": "^11.0"
  }
}
JSON);

            $result = $this->sync(root: $root, features: ['latte']);
            $composer = $this->composer(root: $root);
            $developmentRequirements = $composer['require-dev'] ?? [];
            self::assertIsArray($developmentRequirements);

            self::assertSame(['latte/latte' => '^3.2'], $result['added']);
            self::assertSame('^3.2', $this->runtimeRequirement(root: $root, package: 'latte/latte'));
            self::assertArrayNotHasKey('latte/latte', $developmentRequirements);
            self::assertSame('^11.0', $developmentRequirements['phpunit/phpunit'] ?? null);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testFeaturelessScaffoldDoesNotRequireOrReadComposerManifest(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeComposer($root, '{invalid json');

            $result = $this->sync(root: $root, features: []);

            self::assertSame([], $result['required']);
            self::assertSame([], $result['messages']);
            self::assertSame('{invalid json' . "\n", $this->read(path: $root . '/composer.json'));
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testPsr4EntryIsAddedAndSecondRunIsIdempotent(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeComposer($root, <<<'JSON'
{
  "name": "example/app",
  "require": {
    "php": "^8.5"
  }
}
JSON);

            $result = $this->sync(
                root: $root,
                features: [],
                psr4: ['App\\Todo\\' => 'apps/todo/src/'],
            );

            self::assertSame(['App\\Todo\\' => 'apps/todo/src/'], $result['autoloadAdded']);
            self::assertContains('composer dump-autoload', $result['messages']);
            self::assertSame('apps/todo/src/', $this->psr4Path(root: $root, namespace: 'App\\Todo\\'));

            $before = $this->read(path: $root . '/composer.json');
            $secondResult = $this->sync(
                root: $root,
                features: [],
                psr4: ['App\\Todo\\' => 'apps/todo/src/'],
            );

            self::assertSame([], $secondResult['autoloadAdded']);
            self::assertSame(['App\\Todo\\' => 'apps/todo/src/'], $secondResult['autoloadPresent']);
            self::assertSame([], $secondResult['messages']);
            self::assertSame($before, $this->read(path: $root . '/composer.json'));
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testConflictingPsr4EntryIsRejectedWithoutRewritingManifest(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeComposer($root, <<<'JSON'
{
  "name": "example/app",
  "autoload": {
    "psr-4": {
      "App\\Todo\\": "src/Todo/"
    }
  }
}
JSON);
            $before = $this->read(path: $root . '/composer.json');

            try {
                $this->sync(
                    root: $root,
                    features: [],
                    psr4: ['App\\Todo\\' => 'apps/todo/src/'],
                );
                self::fail('Conflicting PSR-4 entry should fail.');
            } catch (RuntimeException $exception) {
                self::assertStringContainsString('already mapped to a different path', $exception->getMessage());
            }

            self::assertSame($before, $this->read(path: $root . '/composer.json'));
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testMissingManifestReturnsExplicitRequireCommandWithoutCreatingOne(): void
    {
        $root = $this->temporaryRoot();

        try {
            $result = $this->sync(root: $root, features: ['latte']);

            self::assertTrue($result['missingManifest']);
            self::assertSame(['latte/latte' => '^3.0'], $result['required']);
            self::assertContains('composer require latte/latte:^3.0', $result['messages']);
            self::assertFileDoesNotExist($root . '/composer.json');
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testInvalidManifestFailsBeforeItCanBeRewritten(): void
    {
        $root = $this->temporaryRoot();

        try {
            $this->writeComposer($root, '{invalid json');

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Composer manifest is not valid JSON');

            $this->sync(root: $root, features: ['latte']);
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    private function temporaryRoot(): string
    {
        $root = sys_get_temp_dir() . '/webmedia-scaffold-composer-test-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($root, recursive: true));

        return $root;
    }

    private function writeComposer(string $root, string $contents): void
    {
        self::assertNotFalse(file_put_contents($root . '/composer.json', rtrim($contents) . "\n"));
    }

    /**
     * @param list<string> $features
     * @param array<string, string> $psr4
     * @return array{
     *     required: array<string, string>,
     *     added: array<string, string>,
     *     present: array<string, string>,
     *     autoloadRequired: array<string, string>,
     *     autoloadAdded: array<string, string>,
     *     autoloadPresent: array<string, string>,
     *     missingManifest: bool,
     *     messages: list<string>
     * }
     */
    private function sync(string $root, array $features, array $psr4 = []): array
    {
        /**
         * @var Closure(string, list<string>, array<string, string>): array{
         *     required: array<string, string>,
         *     added: array<string, string>,
         *     present: array<string, string>,
         *     autoloadRequired: array<string, string>,
         *     autoloadAdded: array<string, string>,
         *     autoloadPresent: array<string, string>,
         *     missingManifest: bool,
         *     messages: list<string>
         * } $sync
         */
        $sync = require dirname(__DIR__, 2) . '/bin/scaffold-composer-requirements.php';

        return $sync($root, $features, $psr4);
    }

    /** @return array<string, mixed> */
    private function composer(string $root): array
    {
        $composer = json_decode($this->read(path: $root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);

        /** @var array<string, mixed> $composer */
        return $composer;
    }

    private function runtimeRequirement(string $root, string $package): ?string
    {
        $composer = $this->composer(root: $root);
        $requirements = $composer['require'] ?? [];
        self::assertIsArray($requirements);
        $constraint = $requirements[$package] ?? null;
        self::assertTrue($constraint === null || is_string($constraint));

        return $constraint;
    }

    private function psr4Path(string $root, string $namespace): ?string
    {
        $composer = $this->composer(root: $root);
        $autoload = $composer['autoload'] ?? [];
        self::assertIsArray($autoload);
        $psr4 = $autoload['psr-4'] ?? [];
        self::assertIsArray($psr4);
        $path = $psr4[$namespace] ?? null;
        self::assertTrue($path === null || is_string($path));

        return $path;
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
