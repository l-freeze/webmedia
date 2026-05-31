<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use App\Framework\Http\Response;
use Nyholm\Psr7\Response as PsrResponse;
use Psr\Http\Message\ResponseInterface;

final readonly class RoadRunnerResponseMapper
{
    public function map(Response $response): ResponseInterface
    {
        return new PsrResponse(
            status: $response->status,
            headers: $response->headers,
            body: $response->body,
        );
    }
}
