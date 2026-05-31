<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Runtime\ClassicRuntime;
use App\Framework\Runtime\FrankenWorkerRuntime;
use App\Framework\Runtime\RuntimeFactory;
use PHPUnit\Framework\TestCase;

final class RuntimeTest extends TestCase
{
    public function testClassicRuntimeHandlesOneRequest(): void
    {
        $handled = 0;

        (new ClassicRuntime())->run(handleRequest: function () use (&$handled): void {
            $handled++;
        });

        self::assertSame(1, $handled);
    }

    public function testFrankenWorkerRuntimeHandlesRequestsUntilWorkerStops(): void
    {
        $handled = 0;
        $workerCalls = 0;
        $runtime = new FrankenWorkerRuntime(
            frankenHandleRequest: static function (callable $handleRequest) use (&$workerCalls): bool {
                if ($workerCalls >= 2) {
                    return false;
                }

                $workerCalls++;
                $handleRequest();

                return $workerCalls < 2;
            },
        );

        $runtime->run(handleRequest: function () use (&$handled): void {
            $handled++;
        });

        self::assertSame(2, $handled);
    }

    public function testRuntimeFactoryFallsBackToClassicWhenFrankenWorkerIsUnavailable(): void
    {
        $runtime = RuntimeFactory::fromRuntime(
            runtime: 'frankenphp-worker',
            frankenAvailable: false,
        );

        self::assertInstanceOf(ClassicRuntime::class, $runtime);
    }

    public function testRuntimeFactoryBuildsFrankenWorkerWhenExplicitlyAvailable(): void
    {
        $runtime = RuntimeFactory::fromRuntime(
            runtime: 'frankenphp-worker',
            frankenAvailable: true,
            frankenHandleRequest: static fn (callable $handleRequest): bool => false,
        );

        self::assertInstanceOf(FrankenWorkerRuntime::class, $runtime);
    }
}
