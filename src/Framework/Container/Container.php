<?php

declare(strict_types=1);

namespace App\Framework\Container;

use Closure;
use ReflectionClass;
use RuntimeException;

final class Container
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, Closure(self): mixed> */
    private array $factories = [];

    /** @var array<string, string> */
    private array $bindings = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
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
            throw new RuntimeException("Circular dependency detected for {$id}");
        }

        $this->resolving[$id] = true;

        try {
            if (isset($this->factories[$id])) {
                return ($this->factories[$id])($this);
            }

            $concrete = $this->bindings[$id] ?? $id;
            return $this->build($concrete);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Service {$class} is not resolvable");
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type === null || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new RuntimeException("Cannot resolve parameter {$parameter->getName()} for {$class}");
            }
            $arguments[] = $this->get($type->getName());
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
