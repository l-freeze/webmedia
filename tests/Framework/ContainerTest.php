<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Container\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContainerTest extends TestCase
{
    public function testResolvesConstructorDependencies(): void
    {
        $container = new Container();

        $service = $container->get(NeedsDependency::class);

        self::assertInstanceOf(Dependency::class, $service->dependency);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton(Dependency::class, fn (): Dependency => new Dependency());

        self::assertSame($container->get(Dependency::class), $container->get(Dependency::class));
    }

    public function testHasReturnsTrueForFactoriesAndBindings(): void
    {
        $container = new Container();
        $container->set('factory', fn (): Dependency => new Dependency());
        $container->singleton('singleton', fn (): Dependency => new Dependency());
        $container->bind(Contract::class, Implementation::class);

        self::assertTrue($container->has('factory'));
        self::assertTrue($container->has('singleton'));
        self::assertTrue($container->has(Contract::class));
        self::assertFalse($container->has('missing'));
    }

    public function testThrowsWhenBuiltinParameterCannotBeResolved(): void
    {
        $this->expectException(RuntimeException::class);

        (new Container())->get(NeedsScalar::class);
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
