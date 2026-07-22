<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Framework;

use Pera\Framework\Application\IApplication;
use Pera\Framework\Runtime\ClassicRuntime;
use Pera\Framework\Runtime\FrankenWorkerRuntime;
use Pera\Framework\Runtime\RuntimeFactory;
use PHPUnit\Framework\TestCase;

final class RuntimeTest extends TestCase
{
    public function testClassicRuntimeHandlesOneRequest(): void
    {
        $application = $this->application();

        ob_start();
        (new ClassicRuntime())->run(application: $application);
        $body = (string) ob_get_clean();

        self::assertSame('runtime', $body);
    }

    public function testFrankenWorkerRuntimeHandlesRequestsUntilWorkerStops(): void
    {
        $application = $this->application();
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

        ob_start();
        $runtime->run(application: $application);
        $body = (string) ob_get_clean();

        self::assertSame('runtimeruntime', $body);
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

    public function testRuntimeFactoryFallsBackForUnsupportedRuntime(): void
    {
        $runtime = RuntimeFactory::fromRuntime(
            runtime: 'unknown-worker',
            frankenAvailable: false,
        );

        self::assertInstanceOf(ClassicRuntime::class, $runtime);
    }

    private function application(): IApplication
    {
        return new RuntimeApplication();
    }
}

final readonly class RuntimeApplication implements IApplication
{
    public function handleGlobals(): void
    {
        echo 'runtime';
    }
}
