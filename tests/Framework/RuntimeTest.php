<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Application\Application;
use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Runtime\ClassicRuntime;
use App\Framework\Runtime\FrankenWorkerRuntime;
use App\Framework\Runtime\RoadRunnerRuntime;
use App\Framework\Runtime\RuntimeFactory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\RoadRunner\Http\PSR7WorkerInterface;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\WorkerInterface;

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

    public function testRoadRunnerRuntimeMapsRequestAndResponds(): void
    {
        $application = $this->application();
        $worker = new FakePsr7Worker([
            new ServerRequest(method: 'GET', uri: 'https://example.test/runtime', serverParams: [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/runtime',
                'HTTPS' => 'on',
            ]),
        ]);

        (new RoadRunnerRuntime(worker: $worker))->run(application: $application);

        self::assertCount(1, $worker->responses);
        self::assertSame(200, $worker->responses[0]->getStatusCode());
        self::assertSame('runtime', (string) $worker->responses[0]->getBody());
        self::assertSame([], $worker->errors);
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

    public function testRuntimeFactoryFallsBackWhenRoadRunnerIsUnavailable(): void
    {
        $runtime = RuntimeFactory::fromRuntime(
            runtime: 'roadrunner-worker',
            frankenAvailable: false,
            roadRunnerAvailable: false,
        );

        self::assertInstanceOf(ClassicRuntime::class, $runtime);
    }

    public function testRuntimeFactoryBuildsRoadRunnerWhenExplicitlyAvailable(): void
    {
        $runtime = RuntimeFactory::fromRuntime(
            runtime: 'roadrunner-worker',
            frankenAvailable: false,
            roadRunnerAvailable: true,
            roadRunnerRuntime: new RoadRunnerRuntime(worker: new FakePsr7Worker([])),
        );

        self::assertInstanceOf(RoadRunnerRuntime::class, $runtime);
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

final class FakePsr7Worker implements PSR7WorkerInterface
{
    /** @var list<ResponseInterface> */
    public array $responses = [];

    /** @var list<string> */
    public array $errors = [];

    private FakeWorker $worker;

    /** @param list<ServerRequestInterface> $requests */
    public function __construct(private array $requests)
    {
        $this->worker = new FakeWorker(owner: $this);
    }

    public function waitRequest(): ?ServerRequestInterface
    {
        return array_shift($this->requests);
    }

    public function respond(ResponseInterface $response): void
    {
        $this->responses[] = $response;
    }

    public function getWorker(): WorkerInterface
    {
        return $this->worker;
    }
}

final class FakeWorker implements WorkerInterface
{
    public function __construct(private FakePsr7Worker $owner)
    {
    }

    public function waitPayload(): ?Payload
    {
        return null;
    }

    public function respond(Payload $payload): void
    {
    }

    public function error(string $error): void
    {
        $this->owner->errors[] = $error;
    }

    public function stop(): void
    {
    }

    public function hasPayload(?string $class = null): bool
    {
        return false;
    }

    public function getPayload(?string $class = null): ?Payload
    {
        return null;
    }
}
