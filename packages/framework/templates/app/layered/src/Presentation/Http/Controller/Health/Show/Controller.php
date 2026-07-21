<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Presentation\Http\Controller\Health\Show;

use App\{{ namespace }}\Application\Health\Show\Input;
use App\{{ namespace }}\Application\Health\Show\Interactor;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

final readonly class Controller
{
    public function __construct(private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(input: new Input());

        return new Response(
            body: json_encode($output->toArray(), JSON_THROW_ON_ERROR),
            headers: ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }
}
