<?php

declare(strict_types=1);

namespace App\Framework\Container;

use App\Framework\Container\Exception\ContainerBuildException;
use App\Framework\Container\Exception\ServiceNotFoundException;
use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;

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

    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
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

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new ContainerBuildException("Cannot resolve parameter {$parameter->getName()} for {$class}");
            }
            $arguments[] = $this->get(id: $type->getName());
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
