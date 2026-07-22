<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Framework;

use Pera\Framework\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class ContainerTest extends TestCase
{
    public function testResolvesConstructorDependencies(): void
    {
        $service1 = (new Container())->get(id: NeedsDependency::class);
        self::assertInstanceOf(NeedsDependency::class, $service1);
        self::assertInstanceOf(Dependency::class, $service1->dependency);

        $service2 = (new Container())->get(id: NeedsDependency::class);
        self::assertInstanceOf(NeedsDependency::class, $service2);
        self::assertInstanceOf(Dependency::class, $service2->dependency);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton(id: Dependency::class, factory: fn (): Dependency => new Dependency());

        self::assertSame($container->get(id: Dependency::class), $container->get(id: Dependency::class));
    }

    public function testWorkerSingletonReturnsSameInstanceAcrossContainers(): void
    {
        $first = new Container();
        $second = new Container();
        $id = __METHOD__;

        $first->workerSingleton(id: $id, factory: fn (): Dependency => new Dependency());
        $second->workerSingleton(id: $id, factory: fn (): Dependency => new Dependency());

        self::assertSame($first->get(id: $id), $second->get(id: $id));
    }

    public function testHasReturnsTrueForFactoriesAndBindings(): void
    {
        $container = new Container();
        $container->set(id: 'factory', factory: fn (): Dependency => new Dependency());
        $container->singleton(id: 'singleton', factory: fn (): Dependency => new Dependency());
        $container->bind(abstract: IContract::class, concrete: Implementation::class);

        self::assertTrue($container->has(id: 'factory'));
        self::assertTrue($container->has(id: 'singleton'));
        self::assertTrue($container->has(id: IContract::class));
        self::assertFalse($container->has(id: 'missing'));
    }

    public function testThrowsWhenBuiltinParameterCannotBeResolved(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        (new Container())->get(id: NeedsScalar::class);
    }

    public function testThrowsForUnionTypeParameter(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        (new Container())->get(id: NeedsUnionType::class);
    }

    public function testThrowsForUnionTypeParameterWithDefault(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        (new Container())->get(id: NeedsUnionTypeWithDefault::class);
    }

    public function testThrowsNotFoundExceptionForUnknownService(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        (new Container())->get(id: 'missing');
    }

    public function testResolvesMultipleDependencies(): void
    {
        $service1 = (new Container())->get(id: NeedsMultipleDependencies::class);
        self::assertInstanceOf(NeedsMultipleDependencies::class, $service1);
        self::assertInstanceOf(DependencyA::class, $service1->depA);
        self::assertInstanceOf(DependencyB::class, $service1->depB);

        $service2 = (new Container())->get(id: NeedsMultipleDependencies::class);
        self::assertInstanceOf(NeedsMultipleDependencies::class, $service2);
        self::assertInstanceOf(DependencyA::class, $service2->depA);
        self::assertInstanceOf(DependencyB::class, $service2->depB);
    }

    public function testPreservesDefaultParameterValueFromCache(): void
    {
        (new Container())->get(id: NeedsDefaultValue::class);

        $service = (new Container())->get(id: NeedsDefaultValue::class);

        self::assertInstanceOf(NeedsDefaultValue::class, $service);
        self::assertInstanceOf(Dependency::class, $service->dep);
        self::assertSame('default', $service->name);
    }
}

final readonly class Dependency
{
}

final readonly class DependencyA
{
}

final readonly class DependencyB
{
}

final readonly class NeedsDependency
{
    public function __construct(public Dependency $dependency)
    {
    }
}

final readonly class NeedsMultipleDependencies
{
    public function __construct(
        public DependencyA $depA,
        public DependencyB $depB,
    ) {
    }
}

final readonly class NeedsDefaultValue
{
    public function __construct(
        public Dependency $dep,
        public string $name = 'default',
    ) {
    }
}

final readonly class NeedsUnionType
{
    public function __construct(public DependencyA|DependencyB $dep)
    {
    }
}

final class NeedsUnionTypeWithDefault
{
    public function __construct(public Dependency|DependencyA $dep = new Dependency())
    {
    }
}

final readonly class NeedsScalar
{
    public function __construct(public string $name)
    {
    }
}

interface IContract
{
}

final readonly class Implementation implements IContract
{
}
