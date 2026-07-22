<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Presentation\Http\Controller\Index;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

final readonly class Controller
{
    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response(
            body: 'Hello from {{ name }}.',
            headers: ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }
}
