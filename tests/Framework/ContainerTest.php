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
