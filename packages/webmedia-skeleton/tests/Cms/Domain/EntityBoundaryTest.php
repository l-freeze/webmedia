<?php

declare(strict_types=1);

namespace Tests\Cms\Domain;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

final class EntityBoundaryTest extends TestCase
{
    public function testEntitiesDoNotAcceptGenericInputOrRowShapes(): void
    {
        $violations = [];

        foreach ($this->entityClasses() as $className) {
            $entity = new ReflectionClass($className);
            foreach ($entity->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (in_array(strtolower($method->getName()), ['frominput', 'fromrow', 'fromarray', 'hydrate'], true)) {
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

        self::assertSame([], $violations);
    }

    /** @return list<class-string> */
    private function entityClasses(): array
    {
        $files = glob(self::root() . '/apps/cms/src/Domain/Entity/*.php');
        self::assertIsArray($files);

        $classes = [];
        foreach ($files as $file) {
            $className = 'App\\Cms\\Domain\\Entity\\' . pathinfo($file, PATHINFO_FILENAME);
            self::assertTrue(class_exists($className), $className . ' could not be loaded');
            $classes[] = $className;
        }

        return $classes;
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

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
