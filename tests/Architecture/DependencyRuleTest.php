<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use DirectoryIterator;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class DependencyRuleTest extends TestCase
{
    public function testFrontDoesNotDependOnCms(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [self::root() . '/apps/front/src'],
            forbiddenNeedles: ['App\\Cms\\'],
        );
    }

    public function testCmsDoesNotDependOnFront(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [self::root() . '/apps/cms/src'],
            forbiddenNeedles: ['App\\Front\\', 'front.layout_globals'],
        );
    }

    public function testApplicationDoesNotDependOnInfrastructure(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [
                self::root() . '/apps/front/src/Application',
                self::root() . '/apps/cms/src/Application',
            ],
            forbiddenNeedles: ['\\Infrastructure\\'],
        );
    }

    public function testDomainDoesNotDependOnApplicationOrInfrastructure(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [
                self::root() . '/apps/front/src/Domain',
                self::root() . '/apps/cms/src/Domain',
            ],
            forbiddenNeedles: ['\\Application\\', '\\Infrastructure\\'],
        );
    }

    public function testSharedDoesNotDependOnApps(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [self::root() . '/apps/shared/src'],
            forbiddenNeedles: ['App\\Cms\\', 'App\\Front\\'],
        );
    }

    public function testApplicationSecondLevelDirectoriesAreUseCases(): void
    {
        $violations = [];
        foreach ([self::root() . '/apps/front/src/Application', self::root() . '/apps/cms/src/Application'] as $applicationRoot) {
            foreach ($this->secondLevelDirectories(root: $applicationRoot) as $directory) {
                $relativePath = $this->relativePath(path: $directory);
                foreach (['Input.php', 'Interactor.php', 'Output.php'] as $requiredFile) {
                    if (!is_file($directory . '/' . $requiredFile)) {
                        $violations[] = $relativePath . ' does not have ' . $requiredFile;
                    }
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testArticlePermissionValuesAreIssuedOnlyByArticlePolicy(): void
    {
        $violations = [];
        $allowedFile = self::root() . '/apps/cms/src/Domain/Policy/ArticlePolicy.php';
        $permissionClasses = [
            'ArticleCreatePermission',
            'ArticleUpdatePermission',
            'ArticlePublishPermission',
            'ArticleDeletePermission',
            'ArticleRestorePermission',
        ];

        foreach ($this->phpFiles(root: self::root() . '/apps/cms/src') as $file) {
            if ($file->getPathname() === $allowedFile) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);
            foreach ($permissionClasses as $permissionClass) {
                if (str_contains($contents, 'new ' . $permissionClass)) {
                    $violations[] = sprintf(
                        '%s creates %s outside Domain/Policy/ArticlePolicy',
                        $this->relativePath(path: $file->getPathname()),
                        $permissionClass,
                    );
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testDomainEntitiesDoNotAcceptGenericInputOrRowShapes(): void
    {
        $violations = [];
        $entityRoots = [
            self::root() . '/apps/front/src/Domain/Entity' => 'App\\Front\\Domain\\Entity\\',
            self::root() . '/apps/cms/src/Domain/Entity' => 'App\\Cms\\Domain\\Entity\\',
        ];
        $forbiddenFactoryNames = ['frominput', 'fromrow', 'fromarray', 'hydrate'];

        foreach ($entityRoots as $root => $namespace) {
            foreach ($this->phpFiles(root: $root) as $file) {
                $className = $namespace . $file->getBasename('.php');
                self::assertTrue(class_exists($className), $className . ' could not be loaded');

                $entity = new ReflectionClass($className);
                foreach ($entity->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if (in_array(strtolower($method->getName()), $forbiddenFactoryNames, true)) {
                        $violations[] = $className . '::' . $method->getName() . '() is an input or row shape factory';
                    }

                    foreach ($method->getParameters() as $parameter) {
                        if ($this->typeIncludesArray($parameter->getType())) {
                            $violations[] = sprintf(
                                '%s::%s($%s) accepts a generic array',
                                $className,
                                $method->getName(),
                                $parameter->getName(),
                            );
                        }
                    }
                }
            }
        }

        self::assertSame([], $violations);
    }

    /**
     * @param list<string> $roots
     * @param list<string> $forbiddenNeedles
     */
    private function assertNoForbiddenReferences(array $roots, array $forbiddenNeedles): void
    {
        $violations = [];
        foreach ($roots as $root) {
            foreach ($this->phpFiles(root: $root) as $file) {
                $lines = file($file->getPathname());
                self::assertIsArray($lines);

                foreach ($lines as $lineNumber => $line) {
                    foreach ($forbiddenNeedles as $needle) {
                        if (str_contains($line, $needle)) {
                            $violations[] = sprintf(
                                '%s:%d references %s',
                                $this->relativePath(path: $file->getPathname()),
                                $lineNumber + 1,
                                $needle,
                            );
                        }
                    }
                }
            }
        }

        self::assertSame([], $violations);
    }

    private function typeIncludesArray(?ReflectionType $type): bool
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName() === 'array';
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $nestedType) {
                if ($this->typeIncludesArray($nestedType)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            iterator: new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file;
        }

        usort($files, static fn (SplFileInfo $a, SplFileInfo $b): int => $a->getPathname() <=> $b->getPathname());

        return $files;
    }

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function relativePath(string $path): string
    {
        $root = self::root();
        if (str_starts_with($path, $root . '/')) {
            return substr($path, strlen($root) + 1);
        }

        return $path;
    }

    /** @return list<string> */
    private function secondLevelDirectories(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $directories = [];
        foreach (new DirectoryIterator($root) as $feature) {
            if (!$feature->isDir() || $feature->isDot()) {
                continue;
            }

            foreach (new DirectoryIterator($feature->getPathname()) as $action) {
                if (!$action->isDir() || $action->isDot()) {
                    continue;
                }

                $directories[] = $action->getPathname();
            }
        }

        sort($directories);

        return $directories;
    }
}
