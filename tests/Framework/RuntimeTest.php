<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Application\ApplicationInterface;
use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Runtime\ClassicRuntime;
use App\Framework\Runtime\FrankenWorkerRuntime;
use App\Framework\Runtime\RuntimeFactory;
use App\Kernel\Application;
use PHPUnit\Framework\TestCase;

final class RuntimeTest extends TestCase
{
    public function testClassicRuntimeHandlesOneRequest(): void
    {
        $application = $this->application();
        $this->setRequestGlobals(path: '/runtime');

        ob_start();
        (new ClassicRuntime())->run(application: $application);
        $body = (string) ob_get_clean();

        self::assertSame('runtime', $body);
    }

    public function testFrankenWorkerRuntimeHandlesRequestsUntilWorkerStops(): void
    {
        $application = $this->application();
        $this->setRequestGlobals(path: '/runtime');
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

    public function testRuntimeFactoryFallsBackForUnsupportedWorkerRuntime(): void
    {
        $runtime = RuntimeFactory::fromRuntime(
            runtime: 'roadrunner-worker',
            frankenAvailable: false,
        );

        self::assertInstanceOf(ClassicRuntime::class, $runtime);
    }

    public function testRuntimeFactoryCanSelectInjectedRoadRunnerRuntimeWhenAvailable(): void
    {
        $roadRunnerRuntime = new FakeRuntime();

        $runtime = RuntimeFactory::fromRuntime(
            runtime: 'roadrunner-worker',
            frankenAvailable: false,
            roadRunnerAvailable: true,
            roadRunnerRuntime: $roadRunnerRuntime,
        );

        self::assertSame($roadRunnerRuntime, $runtime);
    }

    private function application(): Application
    {
        $root = sys_get_temp_dir() . '/webmedia-runtime-test-' . bin2hex(random_bytes(4));
        mkdir($root . '/apps/front/config', 0777, true);
        mkdir($root . '/apps/cms/config', 0777, true);
        $container = <<<'PHP'
<?php
declare(strict_types=1);
return new \App\Framework\Container\Container();
PHP;
        $routes = <<<'PHP'
<?php
declare(strict_types=1);
return [
    ['GET', '/runtime', \Tests\Framework\RuntimeController::class],
];
PHP;
        file_put_contents($root . '/apps/front/config/container.php', $container);
        file_put_contents($root . '/apps/cms/config/container.php', $container);
        file_put_contents($root . '/apps/front/config/routes.php', $routes);
        file_put_contents($root . '/apps/cms/config/routes.php', "<?php\ndeclare(strict_types=1);\nreturn [];\n");

        return new Application(root: $root);
    }

    private function setRequestGlobals(string $path): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_COOKIE = [];
    }
}

final readonly class RuntimeController
{
    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars): Response
    {
        return new Response(body: 'runtime');
    }
}

final class FakeRuntime implements \App\Framework\Runtime\Runtime
{
    public function run(ApplicationInterface $application): void
    {
    }
}
