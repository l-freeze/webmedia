<?php

declare(strict_types=1);

namespace Pera\Framework\Container;

use Pera\Framework\Container\Exception\ContainerBuildException;
use Pera\Framework\Container\Exception\ServiceNotFoundException;
use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;

final class Container implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, Closure(self): mixed> */
    private array $factories = [];

    /** @var array<string, string> */
    private array $bindings = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    /** @var array<string, mixed> */
    private static array $workerInstances = [];

    /** @var array<string, list<array{name: string, type: string|null, hasDefault: bool, default: mixed}>> */
    private static array $reflectionCache = [];

    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Stores an instance for the current PHP worker lifetime.
     *
     * Do not use for request-scoped services such as Request, Session, or Csrf.
     */
    public function workerSingleton(string $id, Closure $factory): void
    {
        self::$workerInstances[$id] ??= $factory($this);
        $this->instances[$id] = self::$workerInstances[$id];
    }

    public function singleton(string $id, Closure $factory): void
    {
        $this->factories[$id] = function (self $container) use ($id, $factory): mixed {
            return $this->instances[$id] ??= $factory($container);
        };
    }

    public function bind(string $abstract, string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id])
            || isset($this->instances[$id])
            || isset($this->bindings[$id])
            || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new ContainerBuildException("Circular dependency detected for {$id}");
        }

        $this->resolving[$id] = true;

        try {
            if (isset($this->factories[$id])) {
                return ($this->factories[$id])($this);
            }

            if (isset($this->bindings[$id])) {
                return $this->build(class: $this->bindings[$id]);
            }

            if (!class_exists($id)) {
                throw new ServiceNotFoundException("Service {$id} is not registered");
            }

            return $this->build(class: $id);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new ServiceNotFoundException("Service {$class} is not registered");
        }

        if (!isset(self::$reflectionCache[$class])) {
            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            /** @var list<array{name: string, type: string|null, hasDefault: bool, default: mixed}> $params */
            $params = [];
            if ($constructor !== null) {
                foreach ($constructor->getParameters() as $parameter) {
                    $type = $parameter->getType();
                    if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
                        throw new ContainerBuildException("Cannot resolve union or intersection type parameter {$parameter->getName()} for {$class}");
                    }
                    $params[] = [
                        'name' => $parameter->getName(),
                        'type' => $type instanceof ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null,
                        'hasDefault' => $parameter->isDefaultValueAvailable(),
                        'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                    ];
                }
            }
            self::$reflectionCache[$class] = $params;
        }

        $arguments = [];
        foreach (self::$reflectionCache[$class] as $param) {
            if ($param['type'] !== null) {
                $arguments[] = $this->get(id: $param['type']);
            } elseif ($param['hasDefault']) {
                $arguments[] = $param['default'];
            } else {
                throw new ContainerBuildException("Cannot resolve parameter {$param['name']} for {$class}");
            }
        }

        return new $class(...$arguments);
    }
}
