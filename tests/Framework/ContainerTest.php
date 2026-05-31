<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class ContainerTest extends TestCase
{
    public function testResolvesConstructorDependencies(): void
    {
        $container = new Container();

        $service = $container->get(id: NeedsDependency::class);

        self::assertInstanceOf(NeedsDependency::class, $service);
        self::assertInstanceOf(Dependency::class, $service->dependency);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton(id: Dependency::class, factory: fn (): Dependency => new Dependency());

        self::assertSame($container->get(id: Dependency::class), $container->get(id: Dependency::class));
    }

    public function testHasReturnsTrueForFactoriesAndBindings(): void
    {
        $container = new Container();
        $container->set(id: 'factory', factory: fn (): Dependency => new Dependency());
        $container->singleton(id: 'singleton', factory: fn (): Dependency => new Dependency());
        $container->bind(abstract: Contract::class, concrete: Implementation::class);

        self::assertTrue($container->has(id: 'factory'));
        self::assertTrue($container->has(id: 'singleton'));
        self::assertTrue($container->has(id: Contract::class));
        self::assertFalse($container->has(id: 'missing'));
    }

    public function testThrowsWhenBuiltinParameterCannotBeResolved(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        (new Container())->get(id: NeedsScalar::class);
    }

    public function testThrowsNotFoundExceptionForUnknownService(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        (new Container())->get(id: 'missing');
    }
}

final readonly class Dependency
{
}

final readonly class NeedsDependency
{
    public function __construct(public Dependency $dependency)
    {
    }
}

final readonly class NeedsScalar
{
    public function __construct(public string $name)
    {
    }
}

interface Contract
{
}

final readonly class Implementation implements Contract
{
}
