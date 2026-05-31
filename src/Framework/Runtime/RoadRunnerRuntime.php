<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use App\Framework\Application\Application;
use Nyholm\Psr7\Factory\Psr17Factory;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Http\PSR7WorkerInterface;
use Spiral\RoadRunner\Worker;
use Throwable;

final readonly class RoadRunnerRuntime implements Runtime
{
    public function __construct(
        private PSR7WorkerInterface $worker,
        private RoadRunnerRequestMapper $requestMapper = new RoadRunnerRequestMapper(),
        private RoadRunnerResponseMapper $responseMapper = new RoadRunnerResponseMapper(),
    ) {
    }

    public static function create(): self
    {
        $factory = new Psr17Factory();

        return new self(
            worker: new PSR7Worker(
                worker: Worker::create(),
                requestFactory: $factory,
                streamFactory: $factory,
                uploadsFactory: $factory,
            ),
        );
    }

    public function run(Application $application): void
    {
        while (($psrRequest = $this->worker->waitRequest()) !== null) {
            try {
                $request = $this->requestMapper->map(request: $psrRequest);
                $response = $application->handleWorkerRequest(
                    request: $request,
                    cookies: RequestCookieMap::fromMixed(cookies: $psrRequest->getCookieParams()),
                );
                $this->worker->respond(response: $this->responseMapper->map(response: $response));
            } catch (Throwable $throwable) {
                $this->worker->getWorker()->error((string) $throwable);
            } finally {
                $this->requestMapper->cleanup();
            }
        }
    }
}
